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

namespace Acl\Controller\Component;

use Acl\AclInterface;
use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Core\Exception\Exception;

/**
 * Access Control List factory class.
 *
 * Uses a strategy pattern to allow custom ACL implementations to be used with the same component interface.
 * You can define by changing `Configure::write('Acl.classname', 'DbAcl');` in your App/Config/app.php. The adapter
 * you specify must implement `AclInterface`
 *
 * @link http://book.cakephp.org/2.0/en/core-libraries/components/access-control-lists.html
 */
class AclComponent extends Component
{
    /**
     * Instance of an ACL class
     *
     * @var \Acl\AclInterface
     */
    protected $_Instance = null;

    /**
     * Aro object.
     *
     * @var string
     */
    public $Aro;

    /**
     * Aco object
     *
     * @var string
     */
    public $Aco;

    /**
     * Constructor. Will return an instance of the correct ACL class as defined in `Configure::read('Acl.classname')`
     *
     * @param \Cake\Controller\ComponentRegistry $collection A ComponentRegistry
     * @param array $config Array of configuration settings
     * @throws \Cake\Core\Exception\Exception when Acl.classname could not be loaded.
     */
    public function __construct(ComponentRegistry $collection, array $config = [])
    {
        parent::__construct($collection, $config);
        $className = $name = Configure::read('Acl.classname');
        if (!class_exists($className)) {
            $className = App::className('Acl.' . $name, 'Adapter');
            if (!$className) {
                throw new Exception(sprintf('Could not find %s.', $name));
            }
        }
        $this->adapter($className);
    }

    /**
     * Sets or gets the Adapter object currently in the AclComponent.
     *
     * `$this->Acl->adapter();` will get the current adapter class while
     * `$this->Acl->adapter($obj);` will set the adapter class
     *
     * Will call the initialize method on the adapter if setting a new one.
     *
     * @param \Acl\AclInterface|string $adapter Instance of AclInterface or a string name of the class to use. (optional)
     * @return \Acl\AclInterface|void either null, or the adapter implementation.
     * @throws \Cake\Core\Exception\Exception when the given class is not an instance of AclInterface
     */
    public function adapter($adapter = null)
    {
        if ($adapter) {
            if (is_string($adapter)) {
                $adapter = new $adapter();
            }
            if (!$adapter instanceof AclInterface) {
                throw new Exception('AclComponent adapters must implement AclInterface');
            }
            $this->_Instance = $adapter;
            $this->_Instance->initialize($this);

            return;
        }

        return $this->_Instance;
    }

    /**
     * Pass-thru function for ACL check instance. Check methods
     * are used to check whether or not an ARO can access an ACO
     *
     * @param string|array|\Cake\ORM\Table $aro ARO The requesting object identifier. See `AclNode::node()` for possible formats
     * @param string|array|\Cake\ORM\Table $aco ACO The controlled object identifier. See `AclNode::node()` for possible formats
     * @param string $action Action (defaults to *)
     * @return bool Success
     */
    public function check($aro, $aco, $action = '*')
    {
        return $this->_Instance->check($aro, $aco, $action);
    }

    /**
     * Pass-thru function for ACL allow instance. Allow methods
     * are used to grant an ARO access to an ACO.
     *
     * @param string|array|\Cake\ORM\Table $aro ARO The requesting object identifier. See `AclNode::node()` for possible formats
     * @param string|array|\Cake\ORM\Table $aco ACO The controlled object identifier. See `AclNode::node()` for possible formats
     * @param string $action Action (defaults to *)
     * @return bool Success
     */
    public function allow($aro, $aco, $action = '*')
    {
        return $this->_Instance->allow($aro, $aco, $action);
    }

    /**
     * Pass-thru function for ACL deny instance. Deny methods
     * are used to remove permission from an ARO to access an ACO.
     *
     * @param string|array|\Cake\ORM\Table $aro ARO The requesting object identifier. See `AclNode::node()` for possible formats
     * @param string|array|\Cake\ORM\Table $aco ACO The controlled object identifier. See `AclNode::node()` for possible formats
     * @param string $action Action (defaults to *)
     * @return bool Success
     */
    public function deny($aro, $aco, $action = '*')
    {
        return $this->_Instance->deny($aro, $aco, $action);
    }

    /**
     * Pass-thru function for ACL inherit instance. Inherit methods
     * modify the permission for an ARO to be that of its parent object.
     *
     * @param string|array|\Cake\ORM\Table $aro ARO The requesting object identifier. See `AclNode::node()` for possible formats
     * @param string|array|\Cake\ORM\Table $aco ACO The controlled object identifier. See `AclNode::node()` for possible formats
     * @param string $action Action (defaults to *)
     * @return bool Success
     */
    public function inherit($aro, $aco, $action = '*')
    {
        return $this->_Instance->inherit($aro, $aco, $action);
    }
}
