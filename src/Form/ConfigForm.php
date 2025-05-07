<?php
declare(strict_types=1);

namespace App\Form;

use Cake\Core\Configure;
use Cake\Form\Form;
use Cake\Form\Schema;
use Cake\Validation\Validator;
use Override;

/**
 * Cms Config Form.
 */
class ConfigForm extends Form
{
    /**
     * @inheritDoc
     */
    #[Override]
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
     * @inheritDoc
     */
    #[Override]
    public function validationDefault(Validator $validator): Validator
    {
        return $validator
                        ->addNested('images', (new Validator())->add('quality', [
                                    'not-blank' => ['rule' => 'notBlank'],
                                    'btw-1-100' => ['rule' => ['range', 1, 100]],
        ]));
    }

    /**
     * @param array<string, mixed> $data
     */
    #[Override]
    protected function _execute(array $data): bool
    {
        Configure::write($data);

        return Configure::dump('Cms', 'db', array_keys($data));
    }
}
