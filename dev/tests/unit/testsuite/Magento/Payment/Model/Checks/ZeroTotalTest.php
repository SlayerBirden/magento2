<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @copyright   Copyright (c) 2014 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

namespace Magento\Payment\Model\Checks;

class ZeroTotalTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider paymentMethodDataProvider
     * @param string $code
     * @param int $total
     * @param bool $expectation
     */
    public function testIsApplicable($code, $total, $expectation)
    {
        $paymentMethod = $this->getMockBuilder(
            'Magento\Payment\Model\Checks\PaymentMethodChecksInterface'
        )->disableOriginalConstructor()->setMethods([])->getMock();
        if (!$total) {
            $paymentMethod->expects($this->once())->method('getCode')->will($this->returnValue($code));
        }

        $quote= $this->getMockBuilder('Magento\Sales\Model\Quote')->disableOriginalConstructor()->setMethods(
            ['getBaseSubtotal', 'getShippingAddress', '__wakeup']
        )->getMock();
        $shippingAddress = $this->getMockBuilder(
            'Magento\Sales\Model\Quote\Address'
        )->disableOriginalConstructor()->setMethods(['getBaseShippingAmount', '__wakeup'])->getMock();
        $shippingAddress->expects($this->once())->method('getBaseShippingAmount')->will(
            $this->returnValue($total)
        );
        $quote->expects($this->once())->method('getBaseSubtotal')->will($this->returnValue($total));
        $quote->expects($this->once())->method('getShippingAddress')->will($this->returnValue($shippingAddress));

        $model = new ZeroTotal();
        $this->assertEquals($expectation, $model->isApplicable($paymentMethod, $quote));
    }

    /**
     * @return array
     */
    public function paymentMethodDataProvider()
    {
        return [['not_free', 0, false], ['free', 1, true]];
    }
}
