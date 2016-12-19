<?php
/**
 * CoreShop.
 *
 * LICENSE
 *
 * This source file is subject to the GNU General Public License version 3 (GPLv3)
 * For the full copyright and license information, please view the LICENSE.md and gpl-3.0.txt
 * files that are distributed with this source code.
 *
 * @copyright  Copyright (c) 2015-2016 Dominik Pfaffenbauer (https://www.pfaffenbauer.at)
 * @license    https://www.coreshop.org/license     GNU General Public License version 3 (GPLv3)
 */

use CoreShop\Model\Country;
use CoreShop\Controller\Action\Admin;

/**
 * Class CoreShop_Admin_CountryController
 */
class CoreShop_Admin_CountryController extends Admin
{
    public function init()
    {
        parent::init();

        // check permissions
        $notRestrictedActions = ['list'];
        if (!in_array($this->getParam('action'), $notRestrictedActions)) {
            $this->checkPermission('coreshop_permission_countries');
        }
    }

    public function listAction()
    {
        $list = CoreShop\Model\Country::getList();
        $list->setOrder('ASC');
        $list->setOrderKey('name');
        $list->load();

        $countries = [];
        if (is_array($list->getData())) {
            foreach ($list->getData() as $country) {
                $countries[] = $this->getTreeNodeConfig($country);
            }
        }
        $this->_helper->json($countries);
    }

    protected function getTreeNodeConfig(Country $country)
    {
        $tmpCountry = [
            'id' => $country->getId(),
            'text' => $country->getName(),
            'qtipCfg' => [
                'title' => 'ID: '.$country->getId(),
            ],
            'name' => $country->getName(),
            'zone' => $country->getZone() instanceof \CoreShop\Model\Zone ? $country->getZone()->getName() : ''
        ];

        return $tmpCountry;
    }

    public function getAction()
    {
        $id = $this->getParam('id');
        $country = Country::getById($id);

        if ($country instanceof Country) {
            $this->_helper->json(['success' => true, 'data' => $country]);
        } else {
            $this->_helper->json(['success' => false]);
        }
    }

    public function saveAction()
    {
        $id = $this->getParam('id');
        $data = $this->getParam('data');
        $country = Country::getById($id);

        if ($data && $country instanceof Country) {
            $data = \Zend_Json::decode($this->getParam('data'));

            $country->setValues($data);
            $country->save();

            $this->_helper->json(['success' => true, 'data' => $country]);
        } else {
            $this->_helper->json(['success' => false]);
        }
    }

    public function addAction()
    {
        $name = $this->getParam('name');

        if (strlen($name) <= 0) {
            $this->helper->json(['success' => false, 'message' => $this->getTranslator()->translate('Name must be set')]);
        } else {
            $country = Country::create();
            $country->setName($name);
            $country->setActive(1);
            $country->save();

            $this->_helper->json(['success' => true, 'data' => $country]);
        }
    }

    public function deleteAction()
    {
        $id = $this->getParam('id');
        $country = Country::getById($id);

        if ($country instanceof Country) {
            $country->delete();

            $this->_helper->json(['success' => true]);
        }

        $this->_helper->json(['success' => false]);
    }
}
