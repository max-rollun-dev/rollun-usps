<?php
declare(strict_types=1);

namespace rollun\Entity\Supplier;

use rollun\Entity\Product\Item\ItemInterface;
use service\Entity\Api\DataStore\Shipping\BestShipping;

/**
 * Class RockyMountain
 *
 * @author    r.ratsun <r.ratsun.rollun@gmail.com>
 *
 * @copyright Copyright © 2014 Rollun LC (http://rollun.com/)
 * @license   LICENSE.md New BSD License
 */
class RockyMountain extends AbstractSupplier
{
    /**
     * @var string
     */
    protected $zipOriginal = '84663';

    /**
     * @var array
     */
    protected $shippingMethods
        = [
            [
                'name'     => 'Root-RM-PickUp-Usps-FtCls-Package',
                'priority' => 1
            ],
            [
                'name'     => 'Root-RM-DS-Ontrack',
                'priority' => 2
            ],
            [
                'name'     => 'Root-RM-PickUp-Usps-PM-FR-Env',
                'priority' => 3
            ],
            [
                'name'     => 'Root-RM-PickUp-Usps-PM-FR-LegalEnv',
                'priority' => 5
            ],
            [
                'name'     => 'Root-RM-PickUp-Usps-PM-FR-Pad-Env',
                'priority' => 6
            ],
            [
                'name'     => 'Root-RM-DS',
                'priority' => 7
            ],
        ];

    /**
     * @inheritDoc
     */
    public function isInStock(string $rollunId): bool
    {
        $response = BestShipping::httpSend("api/datastore/RockyMountainInventoryCacheDataStore?eq(rollun_id,$rollunId)&limit(20,0)");
        if (empty($response[0])) {
            return false;
        }

        $this->inventory = $response[0];

        return !empty($this->inventory['qty_ut']) || !empty($this->inventory['qty_ky']);
    }

    /**
     * @inheritDoc
     */
    protected function isValid(ItemInterface $item, string $zipDestination, string $shippingMethod): bool
    {
        if ($shippingMethod === 'Root-RM-DS-Ontrack' && empty($item->getAttribute('qty_ut'))) {
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    protected function isUspsValid(ItemInterface $item, string $zipDestination, string $shippingMethod): bool
    {
        $parts = explode('-Usps-', $shippingMethod);
        if (isset($parts[1])) {
            if ($item->getWeight() > 20) {
                return false;
            }

            if ((float)$item->getAttribute('rmatv_price') > 100) {
                return false;
            }

            if (empty($item->getAttribute('qty_ut'))) {
                return false;
            }
        }

        return parent::isUspsValid($item, $zipDestination, $shippingMethod);
    }
}
