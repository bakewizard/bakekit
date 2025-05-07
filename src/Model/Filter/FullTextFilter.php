<?php
declare(strict_types=1);

namespace App\Model\Filter;

use Cake\Database\Driver\Mysql;
use Cake\ORM\Table;
use Exception;
use Search\Model\Filter\Base;

/**
 * FullTextFilter class
 *
 * This class extends the `Search\Model\Filter\Base` class to provide full-text search functionality
 * for CakePHP applications. It supports MySQL's `MATCH ... AGAINST` syntax and allows configuration
 * of match modes and fields for the search query.
 *
 * @package App\Model\Filter
 * @property array $_defaultConfig Default configuration for the filter, including:
 *   - `mode`: Logical operator for combining conditions (default: 'OR').
 *   - `matchMode`: MySQL match mode (default: 'IN NATURAL LANGUAGE MODE').
 *
 * @property array $_validMatchModes List of valid MySQL match modes:
 *   - 'IN NATURAL LANGUAGE MODE'
 *   - 'IN BOOLEAN MODE'
 *   - 'WITH QUERY EXPANSION'
 *   - 'IN NATURAL LANGUAGE MODE WITH QUERY EXPANSION'
 * @method bool process() Processes the full-text search condition and applies it to the query.
 * @method \Cake\ORM\Table|null getRepository() Retrieves the repository instance for the filter.
 * @method void ensureMysql(?\Cake\ORM\Table $repository) Ensures the database connection is MySQL.
 * @method array<string> getFields(?\Cake\ORM\Table $repository) Retrieves the list of fields for the MATCH clause.
 * @method string filter(string $text, string $matchMode) Prepares the search string for full-text search.
 * @throws \Exception If the database engine is not MySQL.
 */
class FullTextFilter extends Base
{
    /**
     * Default configuration for the FullTextFilter.
     *
     * - `mode`: Specifies the logical operator to use when combining search terms.
     *   Default is 'OR'.
     * - `matchMode`: Defines the SQL full-text search mode to be used.
     *   Default is 'IN NATURAL LANGUAGE MODE'.
     *
     * @var array<int, string>
     */
    private array $_validMatchModes = [
        'IN NATURAL LANGUAGE MODE',
        'IN BOOLEAN MODE',
        'WITH QUERY EXPANSION',
        'IN NATURAL LANGUAGE MODE WITH QUERY EXPANSION',
    ];

    /**
     * Process a MATCH condition.
     *
     * Ex. MATCH(title,body) AGAINST ('some text' IN BOOLEAN MODE)
     *
     * @return bool
     */
    public function process(): bool
    {
        $value = $this->value();
        if ($value === null) {
            return false;
        }

        $repository = $this->getRepository();
        $this->ensureMysql($repository);

        $match = implode(',', $this->getFields($repository));
        $matchMode = $this->getConfig('matchMode');

        if (!in_array($matchMode, $this->_validMatchModes, true)) {
            $matchMode = $this->_validMatchModes[0];
        }

        $condition = "MATCH({$match}) AGAINST ('{$this->filter($value,$matchMode)}' {$matchMode})";

        $this->getQuery()->andWhere([$this->getConfig('mode') => [$condition]]);

        return true;
    }

    /**
     * Gets the repository instance.
     *
     * @return \Cake\ORM\Table|null
     */
    private function getRepository(): ?Table
    {
        $repository = $this->manager()->getRepository();

        return $repository instanceof Table ? $repository : null;
    }

    /**
     * Ensures the database connection is MySQL.
     *
     * @param \Cake\ORM\Table|null $repository The repository instance.
     * @return void
     * @throws \Exception If the database engine is not MySQL.
     */
    private function ensureMysql(?Table $repository): void
    {
        if ($repository && !$repository->getConnection()->getDriver() instanceof Mysql) {
            throw new Exception('Only MySQL is supported for full-text search.');
        }
    }

    /**
     * Gets the list of fields to use in the MATCH clause, with optional translation.
     *
     * @param \Cake\ORM\Table|null $repository The repository instance.
     * @return array<string> List of field names.
     */
    private function getFields(?Table $repository): array
    {
        $fields = $this->getConfig('fields');
        $aliasField = $this->getConfig('aliasField');

        if (!$aliasField || !$repository) {
            return $fields;
        }

        if ($repository->hasBehavior('Translate')) {
            /**
             * @phpstan-ignore method.unresolvableReturnType
             */
            $translateBehavior = $repository->getBehavior('Translate');
            $translatedFields = [];
            foreach ($fields as $field) {
                $translatedFields[] = $translateBehavior->translationField($field);
            }

            return $translatedFields;
        }

        return $fields;
    }

    /**
     * Prepares the search string by cleaning and appending wildcard suffixes for BOOLEAN mode.
     *
     * @param string $text Input text.
     * @param string $matchMode The current match mode.
     * @return string Filtered fulltext search query string.
     */
    private function filter(string $text, string $matchMode): string
    {
        $words = explode(' ', preg_replace('/[^\p{L}\p{N}\s\-]/u', '', $text));
        if ($matchMode === 'IN BOOLEAN MODE') {
            foreach ($words as $i => &$word) {
                if (!empty($word) && !str_ends_with($word, '*')) {
                    $words[$i] = $word . '*';
                } elseif (empty($word)) {
                    unset($words[$i]);
                }
            }
        }

        return implode(' ', $words);
    }
}
