<?php
namespace Flowpack\ElasticSearch\ContentRepositoryAdaptor\Eel;

/*                                                                                                  *
 * This script belongs to the TYPO3 Flow package "Flowpack.ElasticSearch.ContentRepositoryAdaptor". *
 *                                                                                                  *
 * It is free software; you can redistribute it and/or modify it under                              *
 * the terms of the GNU Lesser General Public License, either version 3                             *
 *  of the License, or (at your option) any later version.                                          *
 *                                                                                                  *
 * The TYPO3 project - inspiring people to share!                                                   *
 *                                                                                                  */

use TYPO3\Eel\ProtectedContextAwareInterface;
use TYPO3\Flow\Persistence\QueryResultInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

class ElasticSearchQueryResult implements QueryResultInterface, ProtectedContextAwareInterface
{
    /**
     * @var ElasticSearchQuery
     */
    protected $elasticSearchQuery;

    /**
     * @var array
     */
    protected $results = null;

    /**
     * @var integer
     */
    protected $count = null;

    /**
     * @var array
     */
    protected $aggregations = [];

    /**
     * @var array
     */
    protected $facets = [];

    /**
     * @var array
     */
    protected $additionalAggregations = [];

    /**
     * @param ElasticSearchQuery $elasticSearchQuery
     */
    public function __construct(ElasticSearchQuery $elasticSearchQuery)
    {
        $this->elasticSearchQuery = $elasticSearchQuery;
    }

    /**
     * Initialize the results by really executing the query
     */
    protected function initialize()
    {
        if ($this->results === null) {
            $queryBuilder = $this->elasticSearchQuery->getQueryBuilder();
            $this->results = $queryBuilder->fetch();
            $this->count = $queryBuilder->getTotalItems();
            $this->aggregations = $queryBuilder->getAggregations();
            if (isset($this->aggregations['facets'])) {
                foreach ($this->aggregations['facets'] as $aggregationName => $aggregation) {
                    if (is_array($aggregation)) {
                        $this->facets[$aggregationName] = $aggregation[$aggregationName];
                    }
                }
                unset($this->aggregations['facets']);
            }
        }
    }

    /**
     * @return \Flowpack\ElasticSearch\ContentRepositoryAdaptor\Eel\ElasticSearchQuery
     */
    public function getQuery()
    {
        return clone $this->elasticSearchQuery;
    }

    /**
     * {@inheritdoc}
     */
    public function current()
    {
        $this->initialize();
        return current($this->results);
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        $this->initialize();
        return next($this->results);
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        $this->initialize();
        return key($this->results);
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        $this->initialize();
        return current($this->results) !== false;
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->initialize();
        reset($this->results);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetExists($offset)
    {
        $this->initialize();
        return isset($this->results[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function offsetGet($offset)
    {
        $this->initialize();
        return $this->results[$offset];
    }

    /**
     * {@inheritdoc}
     */
    public function offsetSet($offset, $value)
    {
        $this->initialize();
        $this->results[$offset] = $value;
    }

    /**
     * {@inheritdoc}
     */
    public function offsetUnset($offset)
    {
        $this->initialize();
        unset($this->results[$offset]);
    }

    /**
     * {@inheritdoc}
     */
    public function getFirst()
    {
        $this->initialize();
        if (count($this->results) > 0) {
            return array_values($this->results)[0];
        }
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        $this->initialize();
        return $this->results;
    }

    /**
     * {@inheritdoc}
     */
    public function count()
    {
        if ($this->count === null) {
            $this->count = $this->elasticSearchQuery->getQueryBuilder()->count();
        }

        return $this->count;
    }

    /**
     * @return integer the current number of results which can be iterated upon
     * @api
     */
    public function getAccessibleCount()
    {
        $this->initialize();
        return count($this->results);
    }

    /**
     * @return array the aggregations
     * @api
     */
    public function getAggregations()
    {
        $this->initialize();

        return $this->aggregations;
    }

    /**
     * @return array the facets
     * @api
     */
    public function getFacets()
    {
        $this->initialize();

        return $this->facets;
    }

    /**
     * @param string $key
     * @param array $aggregations
     * @return void
     */
    public function setAdditionalAggregations($key, array $aggregations)
    {
        $this->additionalAggregations[$key] = $aggregations;
    }

    /**
     * @param string $key
     * @return array|null
     */
    public function getAdditionalAggregations($key)
    {
        return isset($this->additionalAggregations[$key]) ? $this->additionalAggregations[$key] : null;
    }

    /**
     * Returns the ElasticSearch "hit" (e.g. the raw content being transferred back from ElasticSearch)
     * for the given node.
     *
     * Can be used for example to access highlighting information.
     *
     * @param NodeInterface $node
     * @return array the ElasticSearch hit, or NULL if it does not exist.
     * @api
     */
    public function searchHitForNode(NodeInterface $node)
    {
        return $this->elasticSearchQuery->getQueryBuilder()->getFullElasticSearchHitForNode($node);
    }

    /**
     * @param string $methodName
     * @return boolean
     */
    public function allowsCallOfMethod($methodName)
    {
        return true;
    }
}
