<?php

/**
 * CakePHP(tm) Tests <http://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace Acl\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

/**
 * Short description for class.
 */
class AroTwosFixture extends TestFixture
{
    /**
     * fields property
     *
     * @var array
     */
    public $fields = [
        'id' => ['type' => 'integer'],
        'parent_id' => ['type' => 'integer', 'length' => 10, 'null' => true],
        'model' => ['type' => 'string', 'null' => true],
        'foreign_key' => ['type' => 'integer', 'length' => 10, 'null' => true],
        'alias' => ['type' => 'string', 'default' => ''],
        'lft' => ['type' => 'integer', 'length' => 10, 'null' => true],
        'rght' => ['type' => 'integer', 'length' => 10, 'null' => true],
        '_constraints' => ['primary' => ['type' => 'primary', 'columns' => ['id']]],
    ];

    /**
     * records property
     *
     * @var array
     */
    public $records = [
        ['parent_id' => null, 'model' => null, 'foreign_key' => null, 'alias' => 'root', 'lft' => '1', 'rght' => '20'],
        ['parent_id' => 1, 'model' => 'Group', 'foreign_key' => '1', 'alias' => 'admin', 'lft' => '2', 'rght' => '5'],
        ['parent_id' => 1, 'model' => 'Group', 'foreign_key' => '2', 'alias' => 'managers', 'lft' => '6', 'rght' => '9'],
        ['parent_id' => 1, 'model' => 'Group', 'foreign_key' => '3', 'alias' => 'users', 'lft' => '10', 'rght' => '19'],
        ['parent_id' => 2, 'model' => 'User', 'foreign_key' => '1', 'alias' => 'Bobs', 'lft' => '3', 'rght' => '4'],
        ['parent_id' => 3, 'model' => 'User', 'foreign_key' => '2', 'alias' => 'Lumbergh', 'lft' => '7', 'rght' => '8'],
        ['parent_id' => 4, 'model' => 'User', 'foreign_key' => '3', 'alias' => 'Samir', 'lft' => '11', 'rght' => '12'],
        ['parent_id' => 4, 'model' => 'User', 'foreign_key' => '4', 'alias' => 'Micheal', 'lft' => '13', 'rght' => '14'],
        ['parent_id' => 4, 'model' => 'User', 'foreign_key' => '5', 'alias' => 'Peter', 'lft' => '15', 'rght' => '16'],
        ['parent_id' => 4, 'model' => 'User', 'foreign_key' => '6', 'alias' => 'Milton', 'lft' => '17', 'rght' => '18'],
    ];
}
