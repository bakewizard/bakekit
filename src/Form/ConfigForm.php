<?php

declare(strict_types=1);

namespace App\Form;

use Cake\Core\Configure;
use Cake\Form\Form;
use Cake\Form\Schema;
use Cake\Validation\Validator;

/**
 * Cms Config Form.
 */
class ConfigForm extends Form
{

    /**
     * Builds the schema for the modelless form
     *
     * @param Schema $schema From schema
     * @return $this
     */
    #[\Override]
    protected function _buildSchema(Schema $schema): Schema
    {
        return $schema
                        ->addField('siteName', ['type' => 'string', 'default' => 'BakeKit CMS'])
                        ->addField('theme', 'string')
                        ->addField('defaultDashboard', ['type' => 'string', 'default' => 'System'])
                        ->addField('maintenance.mode', ['type' => 'boolean', 'default' => 0])
                        ->addField('maintenance.allowedIps', 'string')
                        ->addField('maintenance.message', 'text')
                        ->addField('images.format', ['type' => 'string', 'default' => 'jpeg'])
                        ->addField('images.quality', ['type' => 'integer', 'default' => 90]);
    }

    /**
     * Form validation builder
     *
     * @param Validator $validator to use against the form
     * @return Validator
     */
    #[\Override]
    public function validationDefault(Validator $validator): Validator
    {
        return $validator
                        ->addNested('images', (new Validator())->add('quality', [
                                    'not-blank' => ['rule' => 'notBlank'],
                                    'btw-1-100' => ['rule' => ['range', 1, 100]]
        ]));
    }

    /**
     * Defines what to execute once the From is being processed
     *
     * @return bool
     */
    #[\Override]
    protected function _execute(array $data): bool
    {
        Configure::write($data);
        return Configure::dump('Cms', 'db', array_keys($data));
    }
}
