/**
 * CoreShop
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

pimcore.registerNS('pimcore.plugin.coreshop.rules.conditions.weight');

pimcore.plugin.coreshop.rules.conditions.weight = Class.create(pimcore.plugin.coreshop.rules.conditions.abstract, {

    type : 'weight',

    getForm : function () {
        var minWeightValue = null;
        var maxWeightValue = 0;

        if (this.data && this.data.minWeight) {
            minWeightValue = this.data.minWeight;
        }

        if (this.data && this.data.maxWeight) {
            maxWeightValue = this.data.maxWeight;
        }

        var minWeight = new Ext.form.NumberField({
            fieldLabel:t('coreshop_condition_weight_minWeight'),
            name:'minWeight',
            value : minWeightValue,
            minValue : 0,
            decimalPrecision : 0,
            step : 1
        });

        var maxWeight = new Ext.form.NumberField({
            fieldLabel:t('coreshop_condition_weight_maxWeight'),
            name:'maxWeight',
            value : maxWeightValue,
            minValue : 0,
            decimalPrecision : 0,
            step : 1
        });

        this.form = Ext.create('Ext.form.Panel', {
            items : [
                minWeight, maxWeight
            ]
        });

        return this.form;
    }
});
