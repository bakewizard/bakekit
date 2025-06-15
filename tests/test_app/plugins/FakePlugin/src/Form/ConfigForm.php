<?php
declare(strict_types=1);

namespace FakePlugin\Form;

use Cake\Form\Form;
use Cake\Form\Schema;
use Cake\Validation\Validator;

class ConfigForm extends Form
{
    protected function _buildSchema(Schema $schema): Schema
    {
        return $schema
                      ->addField('enabled', ['type' => 'boolean', 'default' => true])
                      ->addField('title', ['type' => 'string', 'default' => 'Fake Plugin']);
    }

    protected function _buildValidator(Validator $validator): Validator
    {
        return $validator;
    }

    protected function _execute(array $data): bool
    {
        // Log or test $data if needed
        return true;
    }
}
