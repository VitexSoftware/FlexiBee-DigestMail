<?php

/**
 * Sent Reminds overview
 *
 * @author vitex
 */
class Reminds extends \FlexiPeeHP\Digest\DigestModule implements \FlexiPeeHP\Digest\DigestModuleInterface
{
    /**
     * Reminds dates
     * @var array 
     */
    public $timeColumn = ['datUp1', 'datUp2', 'datSmir'];

    /**
     * Count several reminds
     * @var array of int
     */
    private $remids = [];

    public function dig()
    {
        $invoicer = new \FlexiPeeHP\FakturaVydana();

        $faDatakturyRaw = $invoicer->getColumnsFromFlexiBee(['kod', 'firma', 'sumCelkem',
            'zbyvaUhradit', 'mena', 'datUp1', 'datUp2', 'datSmir', 'datSplat'],
            $this->condition);

        $invoicer->addStatusMessage("Faktur: ".count($faDatakturyRaw));






        if (empty($faDatakturyRaw)) {
            $this->addItem(_('none'));
        } else {
            $adreser  = new FlexiPeeHP\Adresar(null, ['offline' => 'true']);
            $invTable = new \FlexiPeeHP\Digest\Table([
                _('Company'),
                _('Amount'),
                _('Overdue days'),
                _('Remind #1'),
                _('Remind #2'),
                _('Remind #3')
            ]);

            $overDues = [];

            foreach ($faDatakturyRaw as $invoice => $invoiceData) {

                $this->countReminds($invoiceData);

                $overdueDays = \FlexiPeeHP\FakturaVydana::overdueDays($invoiceData['datSplat']);
                $overDues[]  = $overdueDays;

                $adreser->setMyKey($invoiceData['firma']);

                $nazevFirmy = array_key_exists('firma@showAs', $invoiceData) ? $invoiceData['firma@showAs']
                        : \FlexiPeeHP\FlexiBeeRO::uncode($invoiceData['firma']);

                $invTable->addRowColumns([
                    new \Ease\Html\ATag($adreser->getApiURL(), $nazevFirmy),
                    $invoiceData['zbyvaUhradit'],
                    $overdueDays,
                    empty($invoiceData['datUp1']) ? '' : $this->myDate($invoiceData['datUp1']),
                    empty($invoiceData['datUp2']) ? '' : $this->myDate($invoiceData['datUp2']),
                    empty($invoiceData['datSmir']) ? '' : $this->myDate($invoiceData['datSmir'])
                ]);
            }

            $invTable->addRowFooterColumns([count($faDatakturyRaw), '', (array_sum($overDues)
                / count($overDues)), $this->remids['datUp1'],
                $this->remids['datUp2'], $this->remids['datSmir']]);

            $this->addItem($invTable);
        }
    }

    /**
     * 
     * @param array $rowData
     */
    public function countReminds($rowData)
    {
        if (!empty($rowData['datUp1'])) {
            $this->countRemind($rowData['datUp1'], 'datUp1');
        }
        if (!empty($rowData['datUp2'])) {
            $this->countRemind($rowData['datUp2'], 'datUp2');
        }
        if (!empty($rowData['datSmir'])) {
            $this->countRemind($rowData['datSmir'], 'datSmir');
        }
    }

    /**
     * count date is within digest date interval 
     * 
     * @param string $date
     * @param string $column
     */
    public function countRemind($date, $column)
    {
        if (!array_key_exists($column, $this->remids)) {
            $this->remids[$column] = 0;
        }
        if ($this->isMyDate(\FlexiPeeHP\FlexiBeeRO::flexiDateToDateTime($date))) {
            if (array_key_exists($column, $this->remids)) {
                $this->remids[$column] ++;
            }
        }
    }

    /**
     * bold Date in digest interval
     * 
     * @param string $flexidate
     * 
     * @return mixed
     */
    public function myDate($flexidate)
    {
        if ($this->isMyDate(\FlexiPeeHP\FlexiBeeRO::flexiDateToDateTime($flexidate))) {
            $humanDate = new Ease\Html\StrongTag(self::humanDate($flexidate));
        } else {
            $humanDate = self::humanDate($flexidate);
        }
        return $humanDate;
    }

    function heading()
    {
        return _('Reminds');
    }
}
