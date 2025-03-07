<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace Acl\Test\TestCase\Adapter;

use Acl\Adapter\PhpAcl;
use Acl\Adapter\Utility\PhpAro;
use Acl\Controller\Component\AclComponent;
use Cake\Controller\ComponentRegistry;
use Cake\Core\Configure;
use Cake\TestSuite\TestCase;

/**
 * Test case for the PhpAcl implementation
 */
class PhpAclTest extends TestCase
{
    /**
     * Setup
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        Configure::write('Acl.classname', 'PhpAcl');
        $Collection = new ComponentRegistry();
        $this->PhpAcl = new PhpAcl();
        $this->Acl = new AclComponent($Collection, [
            'adapter' => [
                'config' => APP . 'config/acl',
            ],
        ]);
    }

    /**
     * Test role inheritance
     *
     * @return void
     */
    public function testRoleInheritance()
    {
        $roles = $this->Acl->Aro->roles('User/peter');
        $this->assertEquals(['Role/accounting'], $roles[0]);
        $this->assertEquals(['User/peter'], $roles[1]);

        $roles = $this->Acl->Aro->roles('hardy');
        $this->assertEquals(['Role/database_manager', 'Role/data_acquirer'], $roles[0]);
        $this->assertEquals(['Role/accounting', 'Role/data_analyst'], $roles[1]);
        $this->assertEquals(['Role/accounting_manager', 'Role/reports'], $roles[2]);
        $this->assertEquals(['User/hardy'], $roles[3]);
    }

    /**
     * Test adding a role
     *
     * @return void
     */
    public function testAddRole()
    {
        $this->assertEquals([[PhpAro::DEFAULT_ROLE]], $this->Acl->Aro->roles('foobar'));
        $this->Acl->Aro->addRole(['User/foobar' => 'Role/accounting']);
        $this->assertEquals([['Role/accounting'], ['User/foobar']], $this->Acl->Aro->roles('foobar'));
    }

    /**
     * Test resolving ARO
     *
     * @return void
     */
    public function testAroResolve()
    {
        $this->Acl->Aro->map = [
            'User' => 'FooModel/nickname',
            'Role' => 'FooModel/role',
        ];

        $this->assertSame('Role/default', $this->Acl->Aro->resolve('Foo.bar'));
        $this->assertSame('User/hardy', $this->Acl->Aro->resolve('FooModel/hardy'));
        $this->assertSame('User/hardy', $this->Acl->Aro->resolve('hardy'));
        $this->assertSame('User/hardy', $this->Acl->Aro->resolve(['FooModel' => ['nickname' => 'hardy']]));
        $this->assertSame('Role/admin', $this->Acl->Aro->resolve(['FooModel' => ['role' => 'admin']]));
        $this->assertSame('Role/admin', $this->Acl->Aro->resolve('Role/admin'));

        $this->assertSame('Role/admin', $this->Acl->Aro->resolve('admin'));
        $this->assertSame('Role/admin', $this->Acl->Aro->resolve('FooModel/admin'));
        $this->assertSame('Role/accounting', $this->Acl->Aro->resolve('accounting'));

        $this->assertSame(PhpAro::DEFAULT_ROLE, $this->Acl->Aro->resolve('bla'));
        $this->assertSame(PhpAro::DEFAULT_ROLE, $this->Acl->Aro->resolve(['FooModel' => ['role' => 'hardy']]));
    }

    /**
     * test correct resolution of defined aliases
     *
     * @return void
     */
    public function testAroAliases()
    {
        $this->Acl->Aro->map = [
            'User' => 'User/username',
            'Role' => 'User/group_id',
        ];

        $this->Acl->Aro->aliases = [
            'Role/1' => 'Role/admin',
            'Role/24' => 'Role/accounting',
        ];

        $user = [
            'User' => [
                'username' => 'unknown_user',
                'group_id' => '1',
            ],
        ];
        // group/1
        $this->assertSame('Role/admin', $this->Acl->Aro->resolve($user));
        // group/24
        $this->assertSame('Role/accounting', $this->Acl->Aro->resolve('Role/24'));
        $this->assertSame('Role/accounting', $this->Acl->Aro->resolve('24'));

        // check department
        $user = [
            'User' => [
                'username' => 'foo',
                'group_id' => '25',
            ],
        ];

        $this->Acl->Aro->addRole(['Role/IT' => null]);
        $this->Acl->Aro->addAlias(['Role/25' => 'Role/IT']);
        $this->Acl->allow('Role/IT', '/rules/debugging/*');

        $this->assertEquals([['Role/IT']], $this->Acl->Aro->roles($user));
        $this->assertTrue($this->Acl->check($user, '/rules/debugging/stats/pageload'));
        $this->assertTrue($this->Acl->check($user, '/rules/debugging/sql/queries'));
        // Role/default is allowed users dashboard, but not Role/IT
        $this->assertFalse($this->Acl->check($user, '/controllers/users/dashboard'));

        $this->assertFalse($this->Acl->check($user, '/controllers/invoices/send'));
        // wee add an more specific entry for user foo to also inherit from Role/accounting
        $this->Acl->Aro->addRole(['User/foo' => 'Role/IT, Role/accounting']);
        $this->assertTrue($this->Acl->check($user, '/controllers/invoices/send'));
    }

    /**
     * test check method
     *
     * @return void
     */
    public function testCheck()
    {
        $this->assertTrue($this->Acl->check('jan', '/controllers/users/Dashboard'));
        $this->assertTrue($this->Acl->check('some_unknown_role', '/controllers/users/Dashboard'));
        $this->assertTrue($this->Acl->check('Role/admin', 'foo/bar'));
        $this->assertTrue($this->Acl->check('role/admin', '/foo/bar'));
        $this->assertTrue($this->Acl->check('jan', 'foo/bar'));
        $this->assertTrue($this->Acl->check('users/jan', 'foo/bar'));
        $this->assertTrue($this->Acl->check('Role/admin', 'controllers/bar'));
        $this->assertTrue($this->Acl->check(['Users' => ['username' => 'jan']], '/controllers/bar/bll'));
        $this->assertTrue($this->Acl->check('Role/database_manager', 'controllers/db/create'));
        $this->assertTrue($this->Acl->check('Users/db_manager_2', 'controllers/db/create'));
        $this->assertFalse($this->Acl->check('db_manager_2', '/controllers/users/Dashboard'));

        // inheritance: hardy -> reports -> data_analyst -> database_manager
        $this->assertTrue($this->Acl->check('Users/hardy', 'controllers/db/create'));
        $this->assertFalse($this->Acl->check('Users/jeff', 'controllers/db/create'));

        $this->assertTrue($this->Acl->check('Role/database_manager', 'controllers/db/select'));
        $this->assertTrue($this->Acl->check('Users/db_manager_2', 'controllers/db/select'));
        $this->assertFalse($this->Acl->check('Users/jeff', 'controllers/db/select'));

        $this->assertTrue($this->Acl->check('Role/database_manager', 'controllers/db/drop'));
        $this->assertTrue($this->Acl->check('Users/db_manager_1', 'controllers/db/drop'));
        $this->assertFalse($this->Acl->check('db_manager_2', 'controllers/db/drop'));

        $this->assertTrue($this->Acl->check('db_manager_2', 'controllers/invoices/edit'));
        $this->assertFalse($this->Acl->check('database_manager', 'controllers/invoices/edit'));
        $this->assertFalse($this->Acl->check('db_manager_1', 'controllers/invoices/edit'));

        // Role/manager is allowed /controllers/*/*_manager
        $this->assertTrue($this->Acl->check('stan', 'controllers/invoices/manager_edit'));
        $this->assertTrue($this->Acl->check('Role/manager', 'controllers/baz/manager_foo'));
        $this->assertFalse($this->Acl->check('Users/stan', 'custom/foo/manager_edit'));
        $this->assertFalse($this->Acl->check('stan', 'bar/baz/manager_foo'));
        $this->assertFalse($this->Acl->check('Role/accounting', 'bar/baz/manager_foo'));
        $this->assertFalse($this->Acl->check('accounting', 'controllers/baz/manager_foo'));

        $this->assertTrue($this->Acl->check('Users/stan', 'controllers/articles/edit'));
        $this->assertTrue($this->Acl->check('stan', 'controllers/articles/add'));
        $this->assertTrue($this->Acl->check('stan', 'controllers/articles/publish'));
        $this->assertFalse($this->Acl->check('Users/stan', 'controllers/articles/delete'));
        $this->assertFalse($this->Acl->check('accounting', 'controllers/articles/edit'));
        $this->assertFalse($this->Acl->check('accounting', 'controllers/articles/add'));
        $this->assertFalse($this->Acl->check('role/accounting', 'controllers/articles/publish'));
    }

    /**
     * lhs of defined rules are case insensitive
     *
     * @return void
     */
    public function testCheckIsCaseInsensitive()
    {
        $this->assertTrue($this->Acl->check('hardy', 'controllers/forms/new'));
        $this->assertTrue($this->Acl->check('Role/data_acquirer', 'controllers/forms/new'));
        $this->assertTrue($this->Acl->check('hardy', 'controllers/FORMS/NEW'));
        $this->assertTrue($this->Acl->check('Role/data_acquirer', 'controllers/FORMS/NEW'));
    }

    /**
     * allow should work in-memory
     *
     * @return void
     */
    public function testAllow()
    {
        $this->assertFalse($this->Acl->check('jeff', 'foo/bar'));

        $this->Acl->allow('jeff', 'foo/bar');

        $this->assertTrue($this->Acl->check('jeff', 'foo/bar'));
        $this->assertFalse($this->Acl->check('peter', 'foo/bar'));
        $this->assertFalse($this->Acl->check('hardy', 'foo/bar'));

        $this->Acl->allow('Role/accounting', 'foo/bar');

        $this->assertTrue($this->Acl->check('peter', 'foo/bar'));
        $this->assertTrue($this->Acl->check('hardy', 'foo/bar'));

        $this->assertFalse($this->Acl->check('Role/reports', 'foo/bar'));
    }

    /**
     * deny should work in-memory
     *
     * @return void
     */
    public function testDeny()
    {
        $this->assertTrue($this->Acl->check('stan', 'controllers/baz/manager_foo'));

        $this->Acl->deny('stan', 'controllers/baz/manager_foo');

        $this->assertFalse($this->Acl->check('stan', 'controllers/baz/manager_foo'));
        $this->assertTrue($this->Acl->check('Role/manager', 'controllers/baz/manager_foo'));
        $this->assertTrue($this->Acl->check('stan', 'controllers/baz/manager_bar'));
        $this->assertTrue($this->Acl->check('stan', 'controllers/baz/manager_foooooo'));
    }

    /**
     * test that a deny rule wins over an equally specific allow rule
     *
     * @return void
     */
    public function testDenyRuleIsStrongerThanAllowRule()
    {
        $this->assertFalse($this->Acl->check('peter', 'baz/bam'));
        $this->Acl->allow('peter', 'baz/bam');
        $this->assertTrue($this->Acl->check('peter', 'baz/bam'));
        $this->Acl->deny('peter', 'baz/bam');
        $this->assertFalse($this->Acl->check('peter', 'baz/bam'));

        $this->assertTrue($this->Acl->check('stan', 'controllers/reports/foo'));
        // stan is denied as he's sales and sales is denied /controllers/*/delete
        $this->assertFalse($this->Acl->check('stan', 'controllers/reports/delete'));
        $this->Acl->allow('stan', 'controllers/reports/delete');
        $this->assertFalse($this->Acl->check('Role/sales', 'controllers/reports/delete'));
        $this->assertTrue($this->Acl->check('stan', 'controllers/reports/delete'));
        $this->Acl->deny('stan', 'controllers/reports/delete');
        $this->assertFalse($this->Acl->check('stan', 'controllers/reports/delete'));

        // there is already an equally specific deny rule that will win
        $this->Acl->allow('stan', 'controllers/reports/delete');
        $this->assertFalse($this->Acl->check('stan', 'controllers/reports/delete'));
    }

    /**
     * test that an invalid configuration throws exception
     *
     * @return void
     */
    public function testInvalidConfigWithAroMissing()
    {
        $this->expectException(\Cake\Core\Exception\Exception::class);
        $this->expectExceptionMessage('"roles" section not found in ACL configuration');
        $config = ['aco' => ['allow' => ['foo' => '']]];
        $this->PhpAcl->build($config);
    }

    /**
     * test that an invalid config with missing acos
     *
     * @return void
     */
    public function testInvalidConfigWithAcosMissing()
    {
        $this->expectException(\Cake\Core\Exception\Exception::class);
        $this->expectExceptionMessage('Neither "allow" nor "deny" rules were provided in ACL configuration.');
        $config = [
            'roles' => ['Role/foo' => null],
        ];

        $this->PhpAcl->build($config);
    }

    /**
     * test resolving of ACOs
     *
     * @return void
     */
    public function testAcoResolve()
    {
        $this->assertEquals(['foo', 'bar'], $this->Acl->Aco->resolve('foo/bar'));
        $this->assertEquals(['foo', 'bar'], $this->Acl->Aco->resolve('foo/bar'));
        $this->assertEquals(['foo', 'bar', 'baz'], $this->Acl->Aco->resolve('foo/bar/baz'));
        $this->assertEquals(['foo', '*-bar', '?-baz'], $this->Acl->Aco->resolve('foo/*-bar/?-baz'));

        $this->assertEquals(['foo', 'bar', '[a-f0-9]{24}', '*_bla', 'bla'], $this->Acl->Aco->resolve('foo/bar/[a-f0-9]{24}/*_bla/bla'));

        // multiple slashes will be squashed to a single, trimmed and then exploded
        $this->assertEquals(['foo', 'bar'], $this->Acl->Aco->resolve('foo//bar'));
        $this->assertEquals(['foo', 'bar'], $this->Acl->Aco->resolve('//foo///bar/'));
        $this->assertEquals(['foo', 'bar'], $this->Acl->Aco->resolve('/foo//bar//'));
        $this->assertEquals(['foo', 'bar'], $this->Acl->Aco->resolve('/foo // bar'));
        $this->assertEquals([], $this->Acl->Aco->resolve('/////'));
    }

    /**
     * test that declaring cyclic dependencies should give an error when building the tree
     *
     * @return void
     */
    public function testAroDeclarationContainsCycles()
    {
        $this->expectError();
        $this->expectExceptionMessage('cycle detected');
        $config = [
            'roles' => [
                'Role/a' => null,
                'Role/b' => 'User/b',
                'User/a' => 'Role/a, Role/b',
                'User/b' => 'User/a',

            ],
            'rules' => [
                'allow' => [
                    '*' => 'Role/a',
                ],
            ],
        ];

        $this->PhpAcl->build($config);
    }

    /**
     * test that with policy allow, only denies count
     *
     * @return void
     */
    public function testPolicy()
    {
        // allow by default
        $this->Acl->setConfig('adapter.policy', PhpAcl::ALLOW);
        $this->Acl->adapter($this->PhpAcl);

        $this->assertTrue($this->Acl->check('Role/sales', 'foo'));
        $this->assertTrue($this->Acl->check('Role/sales', 'controllers/bla/create'));
        $this->assertTrue($this->Acl->check('Role/default', 'foo'));
        // undefined user, undefined aco
        $this->assertTrue($this->Acl->check('foobar', 'foo/bar'));

        // deny rule: Role.sales -> controllers.*.delete
        $this->assertFalse($this->Acl->check('Role/sales', 'controllers/bar/delete'));
        $this->assertFalse($this->Acl->check('Role/sales', 'controllers/bar', 'delete'));
    }
}
