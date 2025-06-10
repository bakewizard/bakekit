<?php
declare(strict_types=1);

namespace App\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * MenuLinksFixture
 */
class MenuLinksFixture extends TestFixture
{
    /**
     * Init method
     *
     * @return void
     */
    public function init(): void
    {
        $this->records = [
            [
                'id' => 2,
                'menu_id' => 2,
                'parent_id' => null,
                'title' => 'Link1',
                'icon' => '',
                'link' => '#',
                'target' => '_self',
                'lft' => 1,
                'rght' => 6,
            ],
            [
                'id' => 3,
                'menu_id' => 2,
                'parent_id' => null,
                'title' => 'Link2',
                'icon' => '',
                'link' => 'http://dummydomain.net',
                'target' => '_self',
                'lft' => 7,
                'rght' => 8,
            ],
            [
                'id' => 4,
                'menu_id' => 2,
                'parent_id' => 2,
                'title' => 'Sublink1',
                'icon' => '',
                'link' => 'http://somedomain.net',
                'target' => '_self',
                'lft' => 2,
                'rght' => 3,
            ],
            [
                'id' => 5,
                'menu_id' => 2,
                'parent_id' => 2,
                'title' => 'Sublink2',
                'icon' => '',
                'link' => 'http://anotherdomain.net',
                'target' => '_self',
                'lft' => 4,
                'rght' => 5,
            ],
            [
                'id' => 6,
                'menu_id' => 3,
                'parent_id' => null,
                'title' => 'Link1',
                'icon' => '',
                'link' => '#',
                'target' => '_self',
                'lft' => 9,
                'rght' => 12,
            ],
            [
                'id' => 7,
                'menu_id' => 3,
                'parent_id' => null,
                'title' => 'Link2',
                'icon' => '',
                'link' => '#',
                'target' => '_self',
                'lft' => 13,
                'rght' => 14,
            ],
            [
                'id' => 8,
                'menu_id' => 3,
                'parent_id' => 6,
                'title' => 'Sublink1',
                'icon' => '',
                'link' => 'http:/notexisting.net',
                'target' => '_self',
                'lft' => 10,
                'rght' => 11,
            ],
        ];
        parent::init();
    }
}
