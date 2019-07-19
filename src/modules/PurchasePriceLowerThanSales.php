<?php
/**
 *  Purchase Price Lower Than Sales
 *
 * @author vitex
 */
class PurchasePriceLowerThanSales extends \FlexiPeeHP\Digest\DigestModule implements \FlexiPeeHP\Digest\DigestModuleInterface
{
    /**
     * Which records we want to see ?
     * @param array $condition
     */
    public $condition = ['nakupCena' => 'is not empty', 'cenaZakl' => 'is not empty'];

    /**
     * 
     */
    public function dig()
    {
        $pricer      = new \FlexiPeeHP\Cenik();
        $productsRaw = $pricer->getColumnsFromFlexibee(['nazev', 'nakupCena', 'cenaZakl'],
            $this->condition, 'kod');

        $products = [];
        if (!empty($productsRaw)) {
            foreach ($productsRaw as $productsCode => $productsData) {
                if (floatval($productsData['nakupCena']) > floatval($productsData['cenaZakl'])) {
                    $products[$productsData['kod']] = [
                        'kod' => $productsData['kod'],
                        'nazev' => $productsData['nazev'],
                        'nakupCena' => $productsData['nakupCena'],
                        'cenaZakl' => $productsData['cenaZakl'],
                        'provar' => $productsData['nakupCena'] - $productsData['cenaZakl']
                    ];
                }
            }
        }
        if (empty($products)) {
            $this->addItem(_('none'));
        } else {
            $topProductsTable = new \FlexiPeeHP\Digest\Table([
                _('Code'),
                _('Name'),
                _('Buy'),
                _('Sell'),
                _('Difference')
            ]);


            $products = self::reindexArrayBy($products, 'provar');

            krsort($products);

            foreach ($products as $productInfo) {
                $productsCode = $productInfo['kod'];
                $pricer->setMyKey($productsCode);
                $topProductsTable->addRowColumns([
                    new \Ease\Html\ATag($pricer->getApiURL(), $productsCode),
                    $productInfo['nazev'],
                    $productInfo['nakupCena'],
                    $productInfo['cenaZakl'],
                    $productInfo['provar'],
                    ]
                );
            }


            $this->addItem($topProductsTable);

            $this->addItem(new \Ease\Html\DivTag(sprintf(_('%d disadvantageous products'),
                        count($products))));

            return !empty($topProductsTable->getItemsCount());
        }
    }

    public function heading()
    {
        return _('Product purchase price lower than sales');
    }
}