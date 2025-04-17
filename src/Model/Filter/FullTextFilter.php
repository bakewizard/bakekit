<?php

declare(strict_types=1);

namespace App\Model\Filter;

use Cake\Database\Driver\Mysql;
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
        'matchMode' => 'IN NATURAL LANGUAGE MODE'
    ];

    /**
     * Valid match modes
     * 
     * @var array 
     */
    private $_validMatchModes = [
        'IN NATURAL LANGUAGE MODE',
        'IN BOOLEAN MODE',
        'WITH QUERY EXPANSION',
        'IN NATURAL LANGUAGE MODE WITH QUERY EXPANSION'
    ];

    /**
     *  Process a MATCH condition.
     * 
     * Ex. MATCH(title,body) AGAINST ('some text' IN BOOLEAN MODE)
     * 
     * @return bool
     */
    #[\Override]
    public function process(): bool
    {
        $value = $this->value();
        if ($value === null) {
            return false;
        }

        //ensure database engine is MySQL
        if (!$this->manager()->getRepository()->getConnection()->getDriver() instanceof Mysql) {
            throw new \Exception('Only MySQL is supported');
        }

        $match = implode(',', $this->_getFields());
        $matchMode = $this->getConfig('matchMode');

        if (!in_array($matchMode, $this->_validMatchModes)) {
            $matchMode = $this->_validMatchModes[0];
        }

        $condition = "MATCH({$match}) AGAINST ('{$this->_filter($value)}' {$matchMode})";

        $this->getQuery()->andWhere([$this->getConfig('mode') => [$condition]]);

        return true;
    }

    private function _getFields(): array
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

    private function _filter($text): string
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
