<?php
namespace Flowpack\ElasticSearch\ContentRepositoryAdaptor;

use Flowpack\JobQueue\Common\Job\JobInterface;
use Flowpack\JobQueue\Common\Queue\Message;
use Flowpack\JobQueue\Common\Queue\QueueInterface;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface;
use TYPO3\TYPO3CR\Domain\Utility\NodePaths;
use TYPO3\TYPO3CR\Search\Indexer\NodeIndexingManager;

class IndexNodeJob implements JobInterface
{
    /**
     * @var string
     */
    protected $nodeContextPath;

    /**
     * @Flow\Inject
     * @var NodeIndexingManager
     */
    protected $nodeIndexingManager;

    /**
     * @Flow\Inject
     * @var ContextFactoryInterface
     */
    protected $contextFactory;

    public function __construct(NodeInterface $node)
    {
        $this->nodeContextPath = $node->getContextPath();
    }


    /**
     * Execute the job
     *
     * A job should finish itself after successful execution using the queue methods.
     *
     * @param QueueInterface $queue
     * @param Message $message The original message
     * @return boolean TRUE if the job was executed successfully and the message should be finished
     */
    public function execute(QueueInterface $queue, Message $message)
    {
        $contextPathPieces = NodePaths::explodeContextPath($this->nodeContextPath);
        $context = $this->contextFactory->create([
            'workspaceName' => $contextPathPieces['workspaceName'],
            'dimensions' => $contextPathPieces['dimensions'],
            'invisibleContentShown' => true
        ]);

        $node = $context->getNode($contextPathPieces['nodePath']);
        if (!$node instanceof NodeInterface) {
            //
            return;
        }
        $this->nodeIndexingManager->indexNode($node);
        return TRUE;
    }

    /**
     * Get a readable label for the job
     *
     * @return string A label for the job
     */
    public function getLabel()
    {
        return 'Index node "' . $this->nodeContextPath . '"';
    }
}