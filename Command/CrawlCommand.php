<?php

namespace Simgroep\ConcurrentSpiderBundle\Command;

use PhpAmqpLib\Message\AMQPMessage;
use Simgroep\ConcurrentSpiderBundle\Queue;
use Simgroep\ConcurrentSpiderBundle\Indexer;
use Simgroep\ConcurrentSpiderBundle\Spider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\DependencyInjection\ContainerInterface;
use VDB\Uri\Exception\UriSyntaxException;
use VDB\Spider\Discoverer\XPathExpressionDiscoverer;
use VDB\Spider\Filter\Prefetch\AllowedHostsFilter;
use VDB\Spider\Filter\Prefetch\RestrictToBaseUriFilter;
use VDB\Spider\PersistenceHandler\PersistenceHandler;

class CrawlCommand extends Command
{
    private $queue;
    private $indexer;
    private $spider;
    private $output;

    public function __construct(
        Queue $queue,
        Indexer $indexer,
        Spider $spider
    ) {
        $this->queue = $queue;
        $this->indexer = $indexer;
        $this->spider = $spider;

        parent::__construct();
    }

    /**
     * Configure the command options.
     */
    public function configure()
    {
        $this
            ->setName('simgroep:crawl')
            ->setDescription("This command starts listening to the queue and will accept url's to index.");
    }

    /**
     *
     * @param \Symfony\Component\Console\Input\InputInterface   $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return integer
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->queue->listen(array($this, 'crawlUrl'));

        return 1;
    }

    /**
     * Consume a message, extracts the URL from it and crawls the webpage.
     *
     * @param \PhpAmqpLib\Message\AMQPMessage $message
     */
    public function crawlUrl(AMQPMessage $message)
    {
        $data = json_decode($message->body, true);
        $urlToCrawl = $data['uri'];
        $baseUrl = $data['base_url'];
        $allowSubDomains = true;

        if (!$this->areHostsEqual($urlToCrawl, $baseUrl)) {
            $this->queue->rejectMessage($message);

            $this->output->writeLn(sprintf("[x] Skipped %s", $urlToCrawl));
            return;
        }

        if ($this->indexer->isUrlIndexed($urlToCrawl)) {
            $this->queue->rejectMessage($message);

            $this->output->writeLn(sprintf("[x] Skipped %s", $urlToCrawl));
            return;
        }

        $this->output->writeLn(sprintf("[x] Crawling: %s", $urlToCrawl));

        try {
            $this->spider->crawlUrl($urlToCrawl);
        } catch (UriSyntaxException $e) {
            $this->output->writeLn(sprintf('<error>[x] URL %s failed</error>', $urlToCrawl));

            $this->queue->rejectMessageAndRequeue($message);
        }

        $this->queue->acknowledge($message);

    }

    /**
     * Indicates whether the hostname parts of two urls are equal.
     *
     * @param string $firstUrl
     * @param string $secondUrl
     *
     * @return boolean
     */
    private function areHostsEqual($firstUrl, $secondUrl)
    {
        $firstUrl = parse_url($firstUrl);
        $secondUrl = parse_url($secondUrl);

        return ($firstUrl['host'] === $secondUrl['host']);
    }
}
