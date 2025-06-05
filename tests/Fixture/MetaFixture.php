<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * MetaFixture
 */
class MetaFixture extends TestFixture
{
    public string $table = 'meta';

    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => 1,
                'plugin_id' => 1,
                'title' => 'Some title',
                'description' => '<p>Some description</p>',
                'seo_title' => 'Some seo title',
                'seo_description' => 'Some seo description',
                'seo_keywords' => 'Some seo keywords',
            ],
            [
                'id' => 2,
                'plugin_id' => 2,
                'title' => 'Some title',
                'description' => '<p>Some description</p>',
                'seo_title' => 'Some seo title',
                'seo_description' => 'Some seo description',
                'seo_keywords' => 'Some seo keywords',
            ],
        ];
        parent::init();
    }
}
