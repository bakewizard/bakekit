<?php
declare(strict_types=1);

namespace App\Form\Cell;

use Cake\Form\Form;
use Cake\Form\Schema;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Validation\Validator;
use Override;

/**
 * Product cell config Form.
 */
class MenuCellConfigForm extends Form
{
    use LocatorAwareTrait;

    /**
     * Menus
     *
     * @var array<int|string, string>
     */
    private array $menus;

    /**
     * @inheritDoc
     */
    public function __construct()
    {
        parent::__construct();
        $labels = $this->fetchTable('Menus');
        $this->menus = $labels->find('list')
            ->where(['id NOT IN' => [1, 2]])
            ->toArray();
    }

    /**
     * @inheritDoc
     */
    #[Override]
    protected function _buildSchema(Schema $schema): Schema
    {
        return $schema
            ->addField('menu', ['type' => 'integer'])
            ->addField('container', ['type' => 'array'])
            ->addField('item', ['type' => 'array'])
            ->addField('itemLink', ['type' => 'array'])
            ->addField('itemWithDropdown', ['type' => 'array'])
            ->addField('itemWithDropdownLink', ['type' => 'array'])
            ->addField('dropdownMenu', ['type' => 'array'])
            ->addField('dropdownMenuItem', ['type' => 'array'])
            ->addField('dropdownMenuItemLink', ['type' => 'array']);
    }

    /**
     * @inheritDoc
     */
    #[Override]
    public function validationDefault(Validator $validator): Validator
    {
        return $validator
            ->nonNegativeInteger('menu')->requirePresence('menu')
            ->array('container')
            ->array('item')
            ->array('itemLink')
            ->array('itemWithDropdown')
            ->array('itemWithDropdownLink')
            ->array('dropdownMenu')
            ->array('dropdownMenuItem')
            ->array('dropdownMenuItemLink');
    }

    /**
     * Returns menus
     *
     * @return array<int|string, string>
     */
    public function getMenus(): array
    {
        return $this->menus;
    }
}
