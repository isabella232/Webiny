<?php

namespace Apps\Webiny\Php\Entities;

use Apps\Webiny\Php\DevTools\Entity\AbstractEntity;
use Apps\Webiny\Php\PackageManager\App;
use Apps\Webiny\Php\PackageManager\Parser\EntityParser;
use Webiny\Component\Mongo\Index\SingleIndex;
use Webiny\Component\StdLib\StdObject\ArrayObject\ArrayObject;

/**
 * Class UserPermission
 *
 * @property string      $name
 * @property string      $slug
 * @property ArrayObject $permissions
 *
 * @package Apps\Webiny\Php\Entities
 *
 */
class UserPermission extends AbstractEntity
{

    protected static $entityCollection = 'UserPermissions';
    protected static $entityMask = '{name}';

    public function __construct()
    {
        parent::__construct();

        $this->index(new SingleIndex('slug', 'slug', false, true));
        $this->attr('name')->char()->setValidators('required')->setToArrayDefault()->onSet(function ($name) {
            if (!$this->slug && !$this->exists()) {
                $this->slug = $this->str($name)->slug()->val();
            }

            return $name;
        });

        $this->attr('slug')->char()->setValidators('required,unique')->onSet(function ($slug) {
            if (!$slug) {
                return $this->slug;
            }

            return $this->str($slug)->slug()->val();
        })->setToArrayDefault();

        $this->attr('description')->char()->setToArrayDefault();
        $this->attr('roles')->many2many('UserRole2UserPermission')->setEntity('\Apps\Webiny\Php\Entities\UserRole');
        $this->attr('permissions')->object();


        /**
         * @api.name                    List entity API methods
         * @api.description             Lists CRUD and custom entity methods from applications
         * @api.query.exclude   array   Array of entities that must be excluded in the response
         * @api.query.entities  array   Array of entities that must be included in the response
         * @api.query.entity    string  Single entity for which the information is needed
         */
        $this->api('GET', '/entity', function () {
            // Entities listed here will not be returned in the final response.
            $excludeEntities = $this->wRequest()->query('exclude', []);

            $singleEntity = false;
            $multipleEntities = $this->wRequest()->query('entities', false);

            if (!$multipleEntities) {
                $singleEntity = $this->wRequest()->query('entity', false);
                if ($singleEntity) {
                    $multipleEntities = [$singleEntity];
                }
            }

            $entities = [];

            foreach ($this->wApps() as $app) {
                /* @var $app App */
                foreach ($app->getEntities() as $entity) {
                    if (in_array($entity['class'], $excludeEntities)) {
                        continue;
                    }

                    if ($multipleEntities && !in_array($entity['class'], $multipleEntities)) {
                        continue;
                    }

                    $entityParser = new EntityParser($entity['class']);
                    $entity['methods'] = $entityParser->getApiMethods(true);

                    if ($singleEntity && $entity['class'] == $singleEntity) {
                        foreach ($entity['methods'] as &$method) {
                            $method['usages'] = $this->getEntityMethodUsages($entity, $method);
                        }
                        return $entity;
                    }

                    $entities[] = $entity;
                }
            }

            // Additionally, we want to know who else is exposing particular method.
            foreach ($entities as &$entity) {
                foreach ($entity['methods'] as &$method) {
                    $method['usages'] = $this->getEntityMethodUsages($entity, $method);
                }
            }

            return $entities;
        })->setPublic();
    }

    public function checkPermission($item, $permission)
    {
        $class = $this->str($item)->explode('\\')->filter()->values()->val();

        // We ony handle the common namespace structure. Everything else will be ignored and returned as false
        if ($class[3] != 'Entities' && $class[3] != 'Services') {
            return false;
        }

        $key = strtolower($class[3]) . '.' . $item;

        if (!$this->permissions->keyExistsNested($key)) {
            return false;
        }

        return $this->permissions->keyNested($key . '.' . $permission);
    }

    private function getEntityMethodUsages($entity, $method)
    {
        $key = 'permissions.entities.' . $entity['class'] . '.' . $method['key'];
        $params = ['UserPermissions', [$key => true], [], 0, 0, ['projection' => ['_id' => 0, 'id' => 1, 'name' => 1]]];
        $permissions = $this->wDatabase()->find(...$params);

        if (empty($permissions)) {
            return [];
        }

        $ids = [];
        foreach ($permissions as $permission) {
            $ids[$permission['id']] = $permission['name'];
        }

        $permissions = $this->wDatabase()->aggregate('UserRole2UserPermission', [
            ['$match' => ['UserPermission' => ['$in' => array_keys($ids)]]],
            [
                '$lookup' => [
                    'from'         => 'UserRoles',
                    'localField'   => 'UserRole',
                    'foreignField' => 'id',
                    'as'           => 'roles'
                ]
            ],
            [
                '$project' => ['_id' => 0, 'id' => '$UserPermission', 'roles.name' => 1, 'roles.id' => 1]
            ]
        ]);

        return array_map(function ($permission) use ($ids) {
            $permission['name'] = $ids[$permission['id']];

            return $permission;
        }, $permissions->toArray());
    }
}