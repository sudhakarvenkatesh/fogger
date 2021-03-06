<?php

namespace App\Fogger\Data;

use App\Fogger\Recipe\Recipe;
use App\Fogger\Recipe\Table;

class ChunkProducer
{
    private $sourceQuery;

    private $chunkCache;

    private $chunkError;

    public function __construct(
        SourceQuery $sourceQuery,
        ChunkCache $chunkCache,
        ChunkError $chunkError
    ) {
        $this->sourceQuery = $sourceQuery;
        $this->chunkCache = $chunkCache;
        $this->chunkError = $chunkError;
    }

    /**
     * @param Table $table
     * @throws \App\Fogger\Subset\Exception\UnknownSubsetStrategyException
     */
    private function queueTableChunks(Table $table)
    {

        if (null === $table->getSortBy()) {
            $this->sourceQuery->getAllKeysQuery($table);
            $this->chunkCache->pushMessage($table);

            return;
        }

        $result = $this->sourceQuery->getAllKeysQuery($table)->execute();

        $counter = 0;
        $keys = [];

        while ($key = $result->fetchColumn()) {
            $keys[] = $key;
            $counter++;
            if (0 === $counter % $table->getChunkSize()) {
                $this->chunkCache->pushMessage($table, $keys);
                $keys = [];
            }
        }
        if (0 !== $counter % $table->getChunkSize()) {
            $this->chunkCache->pushMessage($table, $keys);
        }
    }

    /**
     * @param Recipe $recipe
     * @throws \App\Fogger\Subset\Exception\UnknownSubsetStrategyException
     */
    public function run(Recipe $recipe)
    {
        $this->chunkCache->reset();
        $this->chunkError->reset();
        foreach ($recipe->getTables() as $table) {
            $this->queueTableChunks($table);
        }
    }
}
