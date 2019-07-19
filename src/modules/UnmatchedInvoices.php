<?php
/*
 * Incoming payments for us
 */

/**
 * Description of IncomingPayments
 *
 * @author vitex
 */
class UnmatchedInvoices extends \FlexiPeeHP\Digest\DigestModule implements \FlexiPeeHP\Digest\DigestModuleInterface
{
    public $timeColumn = 'datVyst';

    /**
     * Process Incoming payments
     * 
     * @return boolean
     */
    public function dig()
    {
        $invoicer  = new FlexiPeeHP\FakturaVydana();
        $adresser  = new FlexiPeeHP\Adresar();
        $proformas = $invoicer->getColumnsFromFlexibee(['kod', 'mena', 'popis', 'sumCelkem',
            'sumCelkemMen', 'stavOdpocetK', 'typDokl', 'firma', 'datVyst'],
            array_merge($this->condition,
                ['typPohybuK' => 'typPohybu.prijem', 'storno' => false,
                    'zuctovano' => false,
                    'typDokl.typDoklK' => 'typDokladu.zalohFaktura',
                    'stavUhrK' => 'stavUhr.uhrazeno']), 'datVyst');
        $total     = [];
        $totals    = [];
        if (empty($proformas)) {
            $this->addItem(_('none'));
        } else {
            $incomesTable = new \FlexiPeeHP\Digest\Table([_('Document'), _('Description'),
                _('Denunc state'), _('Document type'), _('Company'), _('Date'), _('Amount')]);
            foreach ($proformas as $proforma) {

                switch ($proforma['stavOdpocetK']) {
                    case 'stavOdp.komplet':
                    case 'stavOdp.vytvZdd':
                        break;

                    default:
                        unset($proforma['external-ids']);
                        unset($proforma['id']);
                        unset($proforma['typDokl@ref']);
                        $adresser->takeData($proforma);

                        $amount   = self::getAmount($proforma);
                        $currency = self::getCurrency($proforma);
                        if (array_key_exists($currency, $total)) {
                            $total[$currency] += $amount;
                            $totals[$currency] ++;
                        } else {
                            $total[$currency]  = $amount;
                            $totals[$currency] = 1;
                        }

                        $proforma['kod']   = new \FlexiPeeHP\Digest\DocumentLink($proforma['kod'],
                            $invoicer);
                        $proforma['price'] = self::getPrice($proforma);

                        $proforma['firma'] = new FlexiPeeHP\Digest\CompanyLink($proforma['firma'],
                            $adresser);


                        unset($proforma['typDokl']);
                        unset($proforma['sumCelkem']);
                        unset($proforma['sumCelkemMen']);
                        unset($proforma['mena']);
                        unset($proforma['mena@ref']);
                        unset($proforma['mena@showAs']);
                        unset($proforma['stavOdpocetK']);
                        unset($proforma['firma@ref']);
                        unset($proforma['firma@showAs']);
                        $incomesTable->addRowColumns($proforma);

                        break;
                }
            }

            $this->addItem($incomesTable);

            foreach ($total as $currency => $amount) {
                $this->addItem(new \Ease\Html\DivTag($totals[$currency].'x'.' '.self::formatCurrency($amount).'&nbsp;'.$currency));
            }
        }
        return !empty($total);
    }

    public function heading()
    {
        return _('Non-deducted proformas');
    }

    /**
     * Default Description
     * 
     * @return string
     */
    public function description()
    {
        return _('Non-deducted proformas');
    }
}