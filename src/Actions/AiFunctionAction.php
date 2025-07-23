<?php

namespace Ngankt2\FilamentChatAgent\Actions;

use Filament\Panel;

abstract class AiFunctionAction
{
    /**
     * Execute the action to retrieve data or statistics.
     *
     * @param array $parameters Parameters extracted from the question (e.g., username)
     * @return array Result containing data and a user-friendly message
     */
    abstract public function execute(array $parameters = []): array;

    /**
     * Get the list of sample questions associated with this action for embedding.
     *
     * @return array List of questions and their metadata
     */
    abstract public function getQuestions(): array;

    /**
     * Get the function definition for OpenAI API.
     *
     * @return array Function schema for OpenAI
     */
    public function getFunctionDefinition(): array
    {
        return [
            'name' => $this->getFunctionName(),
            'description' => $this->getDescription(),
            'parameters' => $this->getParametersSchema(),
        ];
    }

    /**
     * Get the unique function name for this action.
     *
     * @return string
     */
    public function getFunctionName(): string
    {
        return class_basename(static::class);
    }

    /**
     * Get the description of the action.
     *
     * @return string
     */
    public function getDescription(): string
    {
        return 'Retrieve data or statistics for ' . class_basename(static::class);
    }

    /**
     * Get the parameters schema for the function.
     *
     * @return array
     */
    public function getParametersSchema(): array
    {
        return ['type' => 'object', 'properties' => []];
    }
}