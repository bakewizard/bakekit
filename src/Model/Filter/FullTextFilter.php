<?php
declare(strict_types=1);

namespace App\Model\Filter;

use Cake\Database\Driver\Mysql;
use Exception;
use Override;
use Search\Model\Filter\Base;

class FullTextFilter extends Base
{
    /**
     * Default configuration.
     *
     * @var array
     */
    protected array $_defaultConfig = [
        'mode' => 'OR',
        'matchMode' => 'IN NATURAL LANGUAGE MODE',
    ];

    /**
     * Valid match modes
     *
     * @var array
     */
    private array $_validMatchModes = [
        'IN NATURAL LANGUAGE MODE',
        'IN BOOLEAN MODE',
        'WITH QUERY EXPANSION',
        'IN NATURAL LANGUAGE MODE WITH QUERY EXPANSION',
    ];

    /**
     *  Process a MATCH condition.
     *
     * Ex. MATCH(title,body) AGAINST ('some text' IN BOOLEAN MODE)
     *
     * @return bool
     */
    #[Override]
    public function process(): bool
    {
        $value = $this->value();
        if ($value === null) {
            return false;
        }

        //ensure database engine is MySQL
        if (!$this->manager()->getRepository()->getConnection()->getDriver() instanceof Mysql) {
            throw new Exception('Only MySQL is supported');
        }

        $match = implode(',', $this->getFields());
        $matchMode = $this->getConfig('matchMode');

        if (!in_array($matchMode, $this->_validMatchModes)) {
            $matchMode = $this->_validMatchModes[0];
        }

        $condition = "MATCH({$match}) AGAINST ('{$this->filter($value)}' {$matchMode})";

        $this->getQuery()->andWhere([$this->getConfig('mode') => [$condition]]);

        return true;
    }

    /**
     * Gets the list of fields to use in the MATCH clause, with optional translation.
     *
     * @return array List of field names.
     */
    private function getFields(): array
    {
        $fields = $this->getConfig('fields');
        if (!$this->getConfig('aliasField')) {
            return $fields;
        }

        $repository = $this->manager()->getRepository();

        $return = [];
        foreach ($fields as $field) {
            $return[] = $repository->translationField($field);
        }

        return $return;
    }

    /**
     * Prepares the search string by cleaning and appending wildcard suffixes for BOOLEAN mode.
     *
     * @param string $text Input text.
     * @return string Filtered fulltext search query string.
     */
    private function filter(string $text): string
    {
        $words = explode(' ', preg_replace('/[^\p{L}\p{N}\s\-]/u', '', $text));
        foreach ($words as $i => &$word) {
            if (!empty($word)) {
                $words[$i] = $word . '*';
            } else {
                unset($words[$i]);
            }
        }

        return implode(' ', $words);
    }
}
