<?php

declare(strict_types=1);

namespace App\Form\Cell;

use Cake\Datasource\FactoryLocator;
use Cake\Form\Form;
use Cake\Form\Schema;
use Cake\Validation\Validator;

/**
 * Product cell config Form.
 */
class MenuCellConfigForm extends Form
{

    private $menus;

    public function __construct()
    {
        parent::__construct();
        $labels = FactoryLocator::get('Table')->get('menus');
        $this->menus = $labels->find('list');
    }

    /**
     * Builds the schema for the modelless form
     *
     * @param \Cake\Form\Schema $schema From schema
     * @return \Cake\Form\Schema
     */
    #[\Override]
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
     * Form validation builder
     *
     * @param \Cake\Validation\Validator $validator to use against the form
     * @return \Cake\Validation\Validator
     */
    #[\Override]
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

    public function getMenus()
    {
        return $this->menus;
    }
}
