<?php

class Magestore_Affiliatepluslevel_Model_Observer {

    /**
     * 
     * @param type $observer
     * @return type
     */
    public function joinAccountToReferral($observer) {
        /* hainh edit 25-04-2014: check if the Tier Commission plugin is enable or not */
        if (!Mage::helper('affiliatepluslevel')->isPluginEnabled()) {
            return;
        }

        $collection = $observer->getCollection();
        $tierTable = Mage::getModel('core/resource')->getTableName('affiliatepluslevel_tier');
        $accountTable = Mage::getModel('core/resource')->getTableName('affiliateplus_account');

        $collection->getSelect()
                ->joinLeft($tierTable, "$tierTable.tier_id = main_table.account_id", array('level' => "if ($tierTable.level IS NULL, 0, $tierTable.level)"))
                ->joinLeft($accountTable, "$accountTable.account_id = $tierTable.toptier_id", array('toptier_name' => "if ($accountTable.name IS NULL, 'N/A', $accountTable.name)", 'toptier_id' => "$accountTable.account_id"))
        ;
    }

    /**
     * Add column Upper Tier, Level into Affiliate Account Grid in back-end 
     * @param type $observer
     * @return type
     */
    public function addColumnToAccountGrid($observer) {
        /* hainh edit 25-04-2014 */
        if (!Mage::helper('affiliatepluslevel')->isPluginEnabled()) {
            return;
        }
        $grid = $observer->getGrid();
        $accountTable = Mage::getModel('core/resource')->getTableName('affiliateplus_account');
        $tierTable = Mage::getModel('core/resource')->getTableName('affiliatepluslevel_tier');
        $grid->addColumn('toptier_name', array(
            'header' => Mage::helper('affiliatepluslevel')->__('Upper Tier'),
            'width' => '150px',
            'align' => 'right',
            'index' => 'toptier_name',
            'renderer' => 'affiliatepluslevel/adminhtml_account_renderer_toptier',
            'filter_index' => "$accountTable.name",
                //'filter_condition_callback' => array(Mage::getSingleton('affiliatepluslevel/observer'),'filterToptierAffiliateAccount'),
        ));

        $grid->addColumn('level', array(
            'header' => Mage::helper('affiliatepluslevel')->__('Level'),
            'align' => 'right',
            'index' => 'level',
            'filter_index' => "$tierTable.level",
            'filter_condition_callback' => array(Mage::getSingleton('affiliatepluslevel/observer'), 'filterLevelAffiliateAccount'),
        ));
    }

    /**
     * 
     * @param type $collection
     * @param type $column
     * @return type
     */
    public function filterLevelAffiliateAccount(&$collection, $column) {
        $tierTable = Mage::getModel('core/resource')->getTableName('affiliatepluslevel_tier');
        $value = $column->getFilter()->getValue();
        if (!isset($value))
            return;
        if ($value == 0) {
            $collection->getSelect()->where("$tierTable.level IS NULL");
            return;
        }
        $collection->addFieldToFilter("$tierTable.level", $value);
    }

    /**
     * 
     * @param type $collection
     * @param type $column
     * @return type
     */
    public function filterToptierAffiliateAccount(&$collection, $column) {

        $accountTable = Mage::getModel('core/resource')->getTableName('affiliateplus_account');
        $value = $column->getFilter()->getValue();
        if (!isset($value))
            return;
        if ($value == 'N/A') {
            $collection->getSelect()->where("$accountTable.name IS NULL");
            return;
        }
        $collection->getSelect()->where("$accountTable.name = '$value'");
    }

    /**
     * 
     * @param type $observer
     * @return type
     */
    public function addTabToAccount($observer) {
        /* hainh edit 25-04-2014 */
        if (!Mage::helper('affiliatepluslevel')->isPluginEnabled()) {
            return;
        }
        $form = $observer->getForm();
        $accountId = $observer->getId();
//		if($accountId)
//			$label = Mage::helper('affiliatepluslevel')->__('Change upper tier affiliate');
//		else
//			$label = Mage::helper('affiliatepluslevel')->__('Add upper tier affiliate');
//			
//		$form->addTab('toptier_section', array(
//			'label'     => $label,
//			'title'     => $label,
//			'url'		=> $form->getUrl('affiliateplusleveladmin/*/toptier',array('_current'=>true)),
//			'class'     => 'ajax',
//		));

        if ($accountId) {
            $form->addTabAfter('tier_section', array(
                'label' => Mage::helper('affiliatepluslevel')->__('Tier affiliates'),
                'title' => Mage::helper('affiliatepluslevel')->__('Tier affiliates'),
                'url' => $form->getUrl('adminhtml/affiliatepluslevel_account/tier', array('_current' => true)),
                'class' => 'ajax',
                    ), 'payment_section');

            /* $form->addTab('tier_section', array(
              'label'     => Mage::helper('affiliatepluslevel')->__('Tier affiliates'),
              'title'     => Mage::helper('affiliatepluslevel')->__('Tier affiliates'),
              'url'		=> $form->getUrl('affiliatepluslevel//tier',array('_current'=>true)),
              'class'     => 'ajax',
              )); */

            $form->setActiveTab('form_section');
        }
    }

    /**
     * 
     * @param type $observer
     * @return type
     */
    public function addFieldToAccountFieldset($observer) {
        /* hainh edit 25-04-2014 */
        if (!Mage::helper('affiliatepluslevel')->isPluginEnabled()) {
            return;
        }
        $form = $observer->getForm();
        $fieldset = $observer->getFieldset();
        $loadData = $observer->getLoadData();
        $data = array();
        if (!empty($loadData) && !empty($loadData['account_id'])) {
            $tier = Mage::getModel('affiliatepluslevel/tier')->getCollection()
                    ->addFieldToFilter('tier_id', $loadData['account_id'])
                    ->getFirstItem();
            $data['level'] = $tier->getLevel();
            $data['toptier_id'] = $tier->getToptierId();
            $data['toptier'] = Mage::getModel('affiliateplus/account')->load($data['toptier_id'])->getName();
        }
        $fieldset->addField('toptier', 'text', array(
            'label' => Mage::helper('affiliatepluslevel')->__('Upper tier'),
            'name' => 'toptier',
            'readonly' => true,
            // Changed By Billy Trinh to responsive
            'after_element_html' => '</td><td class="label"><a href="javascript:showSelectTopTier()" title="'
            . Mage::helper('affiliateplus')->__('Change') . '" id="type_id_rotator_banners">'
            . Mage::helper('affiliateplus')->__('Change') . '</a>'
            . '<script type="text/javascript">
                    function showSelectTopTier() {
                        new Ajax.Request("' . Mage::getSingleton('adminhtml/url')->getUrl('adminhtml/affiliatepluslevel_account/toptier', array('_current' => true)) . '", {
                            parameters: {form_key: FORM_KEY, map_toptier_id: $("toptier_id").value || 0},
                            evalScripts: true,
                            onSuccess: function(transport) {
                                TINY.box.show("");
                                $("tinycontent").update(transport.responseText);
                            }
                        });
                    }
			</script>',
//            'after_element_html' => '</td><td class="label"><a href="javascript:showSelectTopTier(this)" title="'
//            . Mage::helper('affiliateplus')->__('Change') . '" id="type_id_rotator_banners">'
//            . Mage::helper('affiliateplus')->__('Change') . '</a>'
//            . '<script type="text/javascript">
//                    function showSelectTopTier(el) {
//                        new Ajax.Request("' . Mage::getSingleton('adminhtml/url')->getUrl('adminhtml/affiliatepluslevel_account/toptier', array('_current' => true)) . '", {
//                            parameters: {form_key: FORM_KEY, map_toptier_id: $("toptier_id").value || 0},
//                            evalScripts: true,
//                            onSuccess: function(transport) {
//
//
//                                ajaxPopup(null,transport.responseTextel,el);
//                             
//                            }
//                        });
//                    }
//                </script>',
        ));

        $fieldset->addField('level', 'text', array(
            'label' => Mage::helper('affiliatepluslevel')->__('Level'),
            'name' => 'level',
            'readonly' => true,
            'note' => Mage::helper('affiliatepluslevel')->__('Depending on upper tier\'s level'),
        ));

        $fieldset->addField('toptier_id', 'hidden', array(
            'name' => 'toptier_id',
        ));

        $form->addValues($data);
    }

    /**
     * 
     * @param type $observer
     * @return type
     */
    public function afterSaveAccount($observer) {
        /* hainh edit 25-04-2014 */
        if (!Mage::helper('affiliatepluslevel')->isPluginEnabled()) {
            return;
        }
        $data = $observer->getPostData();
        $account = $observer->getAccount();

        try {
            if (isset($data['toptier_id']) && $data['toptier_id']) {
                if (empty($data['level']))
                    $data['level'] = Mage::helper('affiliatepluslevel')->getAccountLevel($data['toptier_id']) + 1;
                $tier = Mage::getModel('affiliatepluslevel/tier')->getCollection()
                        ->addFieldToFilter('tier_id', $account->getId())
                        ->getFirstItem()
                        ->setTierId($account->getId())
                        ->setToptierId($data['toptier_id'])
                        ->setLevel($data['level'])
                        ->save();
                $level = $data['level'];
            } else {
                $tier = Mage::getModel('affiliatepluslevel/tier')->getCollection()
                        ->addFieldToFilter('tier_id', $account->getId())
                        ->getFirstItem();
                if ($tier->getId())
                    $tier->delete();
                $level = 0;
            }
            // Update tiers level
            $topTierIds = array($account->getId());
            while ($topTierIds) {
                $tiers = Mage::getResourceModel('affiliatepluslevel/tier_collection')
                        ->addFieldToFilter('toptier_id', array('in' => $topTierIds));
                $topTierIds = array();
                $level++;
                foreach ($tiers as $tier) {
                    $topTierIds[] = $tier->getTierId();
                    $tier->setData('level', $level)->save();
                }
            }
        } catch (Exception $e) {
            
        }
    }

    /**
     * 
     * @param type $observer
     * @return type
     */
    public function addTabToTransaction($observer) {
        /* hainh edit 25-04-2014 */
        if (!Mage::helper('affiliatepluslevel')->isPluginEnabled()) {
            return;
        }
        $form = $observer->getForm();
        $form->addTabAfter('tier_section', array(
            'label' => Mage::helper('affiliatepluslevel')->__('Tier\'s transactions'),
            'title' => Mage::helper('affiliatepluslevel')->__('Tier\'s transactions'),
            'url' => $form->getUrl('adminhtml/affiliatepluslevel_transaction/tier', array('_current' => true)),         //Changed By Adam 29/10/2015: Fix issue of SUPEE 6788 - in Magento 1.9.2.2
            'class' => 'ajax',
        ));
    }

    /**
     * 
     * @param type $observer
     * @return type
     */
    public function accountSaveAfter($observer) {
        /* hainh edit 25-04-2014 */
        if (!Mage::helper('affiliatepluslevel')->isPluginEnabled()) {
            return;
        }
        $toptiersInfo = Mage::helper('affiliateplus/cookie')->getAffiliateInfo();
        $account = $observer->getAffiliateplusAccount();

        $isNew = $account->isObjectNew();
        // Changed By Adam: 22/10/2014
        $toptier = null;
        if ($isNew) {
            foreach ($toptiersInfo as $code => $toptierInfo) {// get first element
                $toptier = $toptierInfo['account'];
                $toptierLevel = Mage::helper('affiliatepluslevel')->getAccountLevel($toptier->getId());
                break;
            }

            if ($toptier && $toptier->getId()) {
                try {
                    $tier = Mage::getModel('affiliatepluslevel/tier')
                            ->setToptierId($toptier->getId())
                            ->setTierId($account->getId())
                            ->setLevel($toptierLevel + 1)
                            ->save();
                } catch (Exception $e) {
                    
                }
            }
        }
    }

    /**
     * get tier calculation helper
     * 
     * @return Magestore_Affiliatepluslevel_Helper_Tier
     */
    protected function _getTierHelper() {
        return Mage::helper('affiliatepluslevel/tier');
    }

    /**
     * 
     * @param type $observer
     * @return type
     */
    public function affiliateplusPrepareProgram($observer) {
        /* hainh edit 25-04-2014 */
        if (!Mage::helper('affiliatepluslevel')->isPluginEnabled()) {
            return;
        }
        $info = $observer->getInfo();

        if ($info->getId()) {
            $info->setTierCommission($this->_getTierHelper()->prepareLabelRates($this->_getTierHelper()->getTierProgramCommissionRates($info)));
            $info->setSecTierCommission($this->_getTierHelper()->prepareLabelRates(
                            $this->_getTierHelper()->getSecTierProgramCommissionRates($info)
            ));
        } else {
            $info->setTierCommission($this->_getTierHelper()->prepareLabelRates($this->_getTierHelper()->getTierCommissionRates()));
            $info->setSecTierCommission($this->_getTierHelper()->prepareLabelRates(
                            $this->_getTierHelper()->getSecTierCommissionRates()
            ));
        }
        if (is_array($info->getTierCommission())) {
            $info->setLevelCount(count($info->getTierCommission()));
        }
        if (is_array($info->getSecTierCommission())) {
            $info->setSecLevelCount(count($info->getSecTierCommission()));
        }
    }

    /**
     * 
     * @param type $observer
     * @return type
     */
    public function calculateTierCommission($observer) {
        /* hainh edit 25-04-2014 */
        if (!Mage::helper('affiliatepluslevel')->isPluginEnabled()) {
            return;
        }
        $account = $observer->getAccount();
        $accountId = $account->getId();
        $item = $observer->getItem();
        $commissionObj = $observer->getCommissionObj();

        $maxCommission = $commissionObj->getProfit();
        $commission = $commissionObj->getCommission();
        $baseItemsPrice = $commissionObj->getBaseItemPrice();       // Changed By Adam 22/07/2014
        // Changed By Adam 14/08/2014: Invoice tung phan
        $affiliateplusCommissionItem = $commissionObj->getAffiliateplusCommissionItem();
        $tierCommission = array(
            '1' => array(
                'account' => $accountId,
                'commission' => $commission,
            )
        );

        $tierId = Mage::helper('affiliatepluslevel')->getToptierIdByTierId($accountId);
        if (Mage::helper('affiliateplus/cookie')->getNumberOrdered() && $this->_getTierHelper()->getConfig('use_sec_tier')
        ) {
            $tierRates = $this->_getTierHelper()->getSecTierCommissionRates();
        } else {
            $tierRates = $this->_getTierHelper()->getTierCommissionRates();
        }
        $maxLevel = $this->_getTierHelper()->getMaxLevel();
        for ($i = 2; $i <= $maxLevel; $i++) {
            if (!$tierId || $commission >= $maxCommission)
                break;
            if ($this->_isTierRecivedCommission($tierId, $item->getStoreId()) && isset($tierRates[$i])) {
                $tierRate = $tierRates[$i];
                if ($tierRate['value'] > 0) {
                    /* Changed By Adam: Commission for whole cart 22/07/2014 */
                    if ($tierRate['type'] == 'cart_fixed') {
                        $tierPrice = $item->getQtyOrdered() * $item->getBasePrice() - $item->getBaseDiscountAmount() - $item->getBaseAffiliateplusAmount();
                        $tierRate['value'] = min($tierRate['value'], $baseItemsPrice);
                        $tierComm = $tierPrice * $tierRate['value'] / $baseItemsPrice;
                    } elseif ($tierRate['type'] == 'fixed') {
                        $tierComm = $item->getQtyOrdered() * $tierRate['value'];
                    } else {
                        $tierComm = $maxCommission * $tierRate['value'] / 100;
                    }
                    if ($commission + $tierComm > $maxCommission)
                        $tierComm = $maxCommission - $commission;
                    if ($tierComm) {
                        // Changed By Adam 14/08/2014: Invoice tung phan
                        $affiliateplusCommissionItem .= $tierComm . ",";

                        $tierCommission[$i] = array(
                            'account' => $tierId,
                            'commission' => $tierComm,
                        );
                        $commission += $tierComm;
                    }
                }
            }
            $tierId = Mage::helper('affiliatepluslevel')->getToptierIdByTierId($tierId);
        }

        $commissionObj->setTierCommission($tierCommission);
        $commissionObj->setCommission($commission);

        // Changed By Adam 14/08/2014: Invoice tung phan        
        $commissionObj->setAffiliateplusCommissionItem($affiliateplusCommissionItem);
    }

    /**
     * 
     * @param type $observer
     * @return type
     */
    public function calculateProgramTierCommission($observer) {
        /* hainh edit 25-04-2014 */
        if (!Mage::helper('affiliatepluslevel')->isPluginEnabled()) {
            return;
        }
        $customerId = Mage::getSingleton('adminhtml/session_quote')->getCustomer()->getId();
        if ($customerId) {
            $accountIdByCustomerId = Mage::getModel('affiliateplus/account')->getCollection()
                    ->addFieldToFilter('customer_id', $customerId)
                    ->getFirstItem()
                    ->getId();
        } else {
            $accountIdByCustomerId = '';
        }
        $account = $observer->getAccount();
        $accountId = $account->getId();
        $item = $observer->getItem();
        $program = $observer->getProgram();
        $commissionObj = $observer->getCommissionObj();

        $maxCommission = $commissionObj->getProfit();
        $commission = $commissionObj->getCommission();

        $baseItemsPrice = $commissionObj->getBaseItemPrice();       // Changed By Adam 22/07/2014
        $affiliateplusCommissionItem = $commissionObj->getAffiliateplusCommissionItem();    // Changed By Adam 14/08/2014

        $tierCommission = array(
            '1' => array(
                'account' => $accountId,
                'commission' => $commission,
            )
        );
        $tierId = Mage::helper('affiliatepluslevel')->getToptierIdByTierId($accountId);
        if (Mage::helper('affiliateplus/cookie')->getNumberOrdered()) {
            if ($program->getUseTierConfig()) {
                $isUseSectier = Mage::helper('affiliatepluslevel/tier')->getConfig('use_sec_tier');
                if (!$isUseSectier) {
                    $tierRates = $this->_getTierHelper()->getTierCommissionRates();
                } else {
                    $tierRates = $this->_getTierHelper()->getSecTierCommissionRates($program->getStoreId());
                }
            } else if ($program->getUseSecTier()) {
                $tierRates = $this->_getTierHelper()->getSecTierProgramCommissionRates($program);
            } else {
                $tierRates = $this->_getTierHelper()->getTierProgramCommissionRates($program);
            }
        } else {
            $tierRates = $this->_getTierHelper()->getTierProgramCommissionRates($program);
        }
        $maxLevel = $this->_getTierHelper()->getProgramMaxLevel($program);
        for ($i = 2; $i <= $maxLevel; $i++) {
            if (!$tierId || $commission >= $maxCommission)
                break;
            if ($this->_isTierRecivedCommission($tierId, $item->getStoreId()) && isset($tierRates[$i])) {
                $tierRate = $tierRates[$i];
                if ($tierRate['value'] > 0) {
                    /* Changed By Adam: Calculate commission for whole cart 22/07/2014 */
                    if ($tierRate['type'] == 'cart_fixed') {
                        $tierPrice = $item->getQtyOrdered() * $item->getBasePrice() - $item->getBaseDiscountAmount() - $item->getBaseAffiliateplusAmount();
                        $tierRate['value'] = min($tierRate['value'], $baseItemsPrice);
                        $tierComm = $tierPrice * $tierRate['value'] / $baseItemsPrice;
                    } elseif ($tierRate['type'] == 'fixed') {
                        $tierComm = $item->getQtyOrdered() * $tierRate['value'];
                    } else {
                        $tierComm = $maxCommission * $tierRate['value'] / 100;
                    }
                    if ($commission + $tierComm > $maxCommission)
                        $tierComm = $maxCommission - $commission;
                    if ($tierComm && $tierId != $accountIdByCustomerId) {
                        $tierCommission[$i] = array(
                            'account' => $tierId,
                            'commission' => $tierComm,
                        );
                        $affiliateplusCommissionItem .= $tierComm . ",";
                        $commission += $tierComm;
                    }
                }
            }
            $tierId = Mage::helper('affiliatepluslevel')->getToptierIdByTierId($tierId);
        }
        $commissionObj->setTierCommission($tierCommission);

        $commissionObj->setCommission($commission);

        $commissionObj->setAffiliateplusCommissionItem($affiliateplusCommissionItem);       // Changed By Adam 14/08/2014: Invoice tung phan
    }

    /**
     * 
     * @param type $tierId
     * @param type $storeId
     * @return boolean
     */
    protected function _isTierRecivedCommission($tierId, $storeId = null) {
        if (!$storeId)
            $storeId = Mage::app()->getStore()->getId();
        $account = Mage::getModel('affiliateplus/account')
                ->setStoreId($storeId)
                ->load($tierId);
        if ($account->getStatus() == 1) {
            $customerId = Mage::getSingleton('customer/session')->getCustomerId();
            if ($account->getCustomerId() != $customerId)
                return true;
        }
        return false;
    }

    /**
     * 
     * @param type $observer
     * @return \Magestore_Affiliatepluslevel_Model_Observer
     */
    public function createdTransactionAndRecalculateCommission($observer) {
        /* hainh edit 25-04-2014 */
        if (!Mage::helper('affiliatepluslevel')->isPluginEnabled()) {
            return;
        }
        $transaction = $observer->getTransaction();
        $tierCommissions = $transaction->getTierCommissions();

        $tierTransactions = array();
        $isStandardTransaction = true;
        foreach ($tierCommissions as $itemId => $tierCommission) {
            foreach ($tierCommission as $level => $accComm) {
                if ($level > 1 && $isStandardTransaction)
                    $isStandardTransaction = false;
                if (isset($tierTransactions[$accComm['account']])) {
                    $tierTransactions[$accComm['account']]['commission'] += $accComm['commission'];
                } else {
                    $tierTransactions[$accComm['account']] = array(
                        'tier_id' => $accComm['account'],
                        'transaction_id' => $transaction->getId(),
                        'level' => $level - 1,
                        'commission' => $accComm['commission'],
                    );
                }
            }
        }
        if ($isStandardTransaction)
            return $this;
        $model = Mage::getModel('affiliatepluslevel/transaction');
        foreach ($tierTransactions as $tierTransaction) {
            $model->setData($tierTransaction);
            $tierCommPlus = $transaction->getPercentPlus() * $model->getCommission() / 100;
            $tierCommPlus += $transaction->getCommissionPlus() * $model->getCommission() / $transaction->getCommission();
            $model->setCommissionPlus($tierCommPlus);
            try {
                $model->setId(null)->save();
                if ($model->getLevel() > 0)
                    $model->sendMailNewTransactionToAccount($transaction);
            } catch (Exception $e) {
                
            }
        }

        return $this;

        /*
          $transaction = $observer->getTransaction();
          $commisson = $transaction->getCommission();
          $commissionPlus = $transaction->getCommissionPlus();
          $percentPlus = $transaction->getPercentPlus();

          $perCommissions = Mage::getStoreConfig('affiliateplus/multilevel/commission_percentage', $transaction->getStoreId());
          $perCommissionsArr = explode(',', $perCommissions);


          $tierId = $transaction->getAccountId();
          //print_r($tierId);die();
          $level = 0;
          $realCommission = 0;
          $realCommissionPlus = 0;
          try{
          foreach ($perCommissionsArr as $perCommission){
          if($this->_isAccountRecivedCommission($tierId, $transaction)){
          $tiertransaction = Mage::getModel('affiliatepluslevel/transaction');
          $tiertransaction->setTierId($tierId)
          ->setTransactionId($transaction->getId())
          ->setLevel($level)
          ->setCommission($perCommission/100*$commisson)
          ->setCommissionPlus($perCommission/100*$commissionPlus + $tiertransaction->getCommission()*$percentPlus/100)
          ->save();

          $realCommission += $perCommission/100*$commisson;
          $realCommissionPlus += $perCommission/100*$commissionPlus;

          //send mail to tier
          if($level > 0)
          $tiertransaction->sendMailNewTransactionToAccount($transaction);
          }

          $toptierId = Mage::helper('affiliatepluslevel')->getToptierIdByTierId($tierId);
          if(!$toptierId)
          break;
          $tierId = $toptierId;
          $level++;
          }

          //set new commission
          $transaction->setCommission($realCommission)
          ->setCommissionPlus($realCommissionPlus);

          }catch(Exception $e){

          }
         */
    }

    /**
     * 
     * @param type $tierId
     * @param type $transaction
     * @return boolean
     */
    protected function _isAccountRecivedCommission($tierId, $transaction) {

        $account = Mage::getModel('affiliateplus/account')
                ->setStoreId($transaction->getStoreId())
                ->load($tierId);
        //account is acvive and not is customer
        if ($account->getStatus() == 1 && $account->getCustomerId() != $transaction->getCustomerId())
            return true;
        else
            return false;
    }

    /**
     * 
     * @param type $observer
     * @return type
     */
    public function completeTransaction($observer) {
        /* hainh edit 25-04-2014 */
        if (!Mage::helper('affiliatepluslevel')->isPluginEnabled()) {
            return;
        }
        $transaction = $observer->getTransaction();
        $tierTransactions = $this->_getTierTransactions($transaction);

        try {
            $account = Mage::getModel('affiliateplus/account')->setStoreId($transaction->getStoreId());
            foreach ($tierTransactions as $tierTransaction) {
                $account->load($tierTransaction->getTierId());
                if ($tierTransaction->getLevel() == 0) {
                    $balanceAdded = $transaction->getCommission() + $transaction->getCommissionPlus() + $transaction->getCommission() * $transaction->getPercentPlus() / 100;
                    $balance = $account->getData('balance') - $balanceAdded + $tierTransaction->getCommission() + $tierTransaction->getCommissionPlus();
//                    $balance = $account->getBalance() - $balanceAdded + $tierTransaction->getCommission() + $tierTransaction->getCommissionPlus();
                } else {
                    $balance = $account->getData('balance') + $tierTransaction->getCommission() + $tierTransaction->getCommissionPlus();
//                    $balance = $account->getBalance() + $tierTransaction->getCommission() + $tierTransaction->getCommissionPlus();
                }
                $account->setBalance($balance)
                        ->save();

                //send mail Complete to tier
                if ($tierTransaction->getLevel() > 0)
                    $tierTransaction->sendMailUpdatedTransactionToAccount($transaction, true);
            }
        } catch (Exception $e) {
            
        }
    }

    /**
     * Changed By Adam to solve the problem of invoice tung phan 22/08/2014
     * @param type $observer
     * @return type1111
     */
    public function update_tiercommission_to_tieraffiliate_partial_invoice($observer) {
        /* hainh edit 25-04-2014 */
        if (!Mage::helper('affiliatepluslevel')->isPluginEnabled()) {
            return;
        }
        $transaction = $observer->getTransaction();
        $item = $observer->getItem();
        $invoiceItem = $observer->getInvoiceItem();

        $affiliateplusCommissionItem = explode(",", $item->getAffiliateplusCommissionItem());


        $tierTransactions = $this->_getTierTransactions($transaction);
        try {
            $account = Mage::getModel('affiliateplus/account')->setStoreId($transaction->getStoreId());
            if (!$invoiceItem) {
                foreach ($tierTransactions as $tierTransaction) {
                    if ($tierTransaction->getLevel()) {
                        $totalCommissionItemLevel = $affiliateplusCommissionItem[$tierTransaction->getLevel()];
                        $commission = $totalCommissionItemLevel * ($item->getQtyInvoiced() - $item->getQtyRefunded()) / $item->getQtyOrdered();

                        $account->load($tierTransaction->getTierId());
                        //                    $account->setBalance($account->getBalance() + $commission)->save();

                        $account->setBalance($account->getData('balance') + $commission)->save();
                    }
                }
            } else {

                foreach ($tierTransactions as $tierTransaction) {
                    if ($tierTransaction->getLevel()) {
                        $totalCommissionItemLevel = $affiliateplusCommissionItem[$tierTransaction->getLevel()];
                        $commission = $totalCommissionItemLevel * $invoiceItem->getQty() / $item->getQtyOrdered();

                        $account->load($tierTransaction->getTierId());
                        //                    $account->setBalance($account->getBalance() + $commission)->save();
                        $account->setBalance($account->getData('balance') + $commission)->save();
                    }

                    //                $orderItem = Mage::getModel('sales/order_id')->load($item->getItemId());
                    //                $transactionCommissionLevel = $totalCommissionItemLevel*$orderItem->getQtyInvoiced()/$orderItem->getQtyOrdered();
                    //                $tierTransaction;
                    //                if ($tierTransaction->getLevel() == 0) {
                    //                    $balanceAdded = $transaction->getCommission() + $transaction->getCommissionPlus() + $transaction->getCommission() * $transaction->getPercentPlus() / 100;
                    //                    $balance = $account->getBalance() - $balanceAdded + $tierTransaction->getCommission() + $tierTransaction->getCommissionPlus();
                    //                } else {
                    //                    $balance = $account->getBalance() + $tierTransaction->getCommission() + $tierTransaction->getCommissionPlus();
                    //                }
                    //                $account->setBalance($balance)
                    //                        ->save();
                }
            }
        } catch (Exception $e) {
            
        }
    }

    /**
     * Changed By Adam to solve the problem of invoice tung phan 22/08/2014
     * @param type $observer
     * @return type
     */
    public function update_tiercommission_to_transaction_partial_invoice($observer) {
        /* hainh edit 25-04-2014 */
        if (!Mage::helper('affiliatepluslevel')->isPluginEnabled()) {
            return;
        }
        $transaction = $observer->getTransaction();
        $order = $observer->getOrder();


        $tierTransactions = $this->_getTierTransactions($transaction);
        try {
            foreach ($tierTransactions as $tierTransaction) {
                $transactionTotal = 0;
                foreach ($order->getAllItems() as $item) {
                    if ($item->getAffiliateplusCommission()) {
                        $affiliateplusCommissionItem = explode(",", $item->getAffiliateplusCommissionItem());
                        $totalCommissionItemLevel = $affiliateplusCommissionItem[$tierTransaction->getLevel()];
                        $transactionTotal += $totalCommissionItemLevel * $item->getQtyInvoiced() / $item->getQtyOrdered();
                    }
                }
                if ($transactionTotal)
                    $tierTransaction->setCommission($transactionTotal)->save();

                //send mail Complete to tier
                if ($tierTransaction->getLevel() > 0)
                    $tierTransaction->sendMailUpdatedTransactionToAccount($transaction, true);
            }
        } catch (Exception $e) {
            print_r($e->getMessage());
            die('z');
        }
    }

    /**
     * 
     * @param type $observer
     * @return type
     */
    public function adminhtmlPrepareCommission($observer) {
        /* hainh edit 25-04-2014 */
        if (!Mage::helper('affiliatepluslevel')->isPluginEnabled()) {
            return;
        }
        $transaction = $observer->getTransaction();
        $tierTransaction = $this->_getTierTransactions($transaction)
                ->addFieldToFilter('level', 0)
                ->getFirstItem();
        if ($tierTransaction && $tierTransaction->getId()) {
            $transaction->setRealTotalCommission(
                    $tierTransaction->getCommission() + $tierTransaction->getCommissionPlus()
            );
        }
    }

    /**
     * Changed By Adam: Update commission for tier transaction
     * @param type $observer
     * @return type
     */
    public function reduceTransaction($observer) {
        /* hainh edit 25-04-2014 */
        if (!Mage::helper('affiliatepluslevel')->isPluginEnabled()) {
            return;
        }
        $transaction = $observer->getTransaction();
        $creditmemo = $observer->getCreditmemo();
        $tierTransactions = $this->_getTierTransactions($transaction);


        $commissionObj = $observer->getCommissionObj();
        $baseReduce = $commissionObj->getBaseReduce();
        $totalReduce = $commissionObj->getTotalReduce();

        try {
            foreach ($tierTransactions as $tierTransaction) {
                $transactionTotal = 0;
                $reduceCommission = 0;
                foreach ($creditmemo->getAllItems() as $item) {
                    $orderItem = $item->getOrderItem();
                    $affiliateplusCommissionItem = explode(",", $orderItem->getAffiliateplusCommissionItem());
                    $totalCommissionItemLevel = $affiliateplusCommissionItem[$tierTransaction->getLevel()];
                    $transactionTotal += $totalCommissionItemLevel * ($item->getQty()) / $orderItem->getQtyOrdered();
                    $reduceCommission += $totalCommissionItemLevel * $item->getQty() / $orderItem->getQtyOrdered();
                }
                if ($tierTransaction->getCommission() <= $transactionTotal) {
                    $tierTransaction->setCommission(0)->save();     // Adam: 22/09/2014: Miss ham save() nen bi sai ket qua khi refund
                } else {
                    $tierTransaction->setCommission($tierTransaction->getCommission() - $transactionTotal)->save();
                }
                $totalCommission = $reduceCommission + $tierTransaction->getCommissionPlus() * $item->getQty() / $orderItem->getQtyOrdered();

                $commissionObj->setBaseReduce($reduceCommission);
                $commissionObj->setTotalReduce($totalCommission);

                // send email to tier
                if ($tierTransaction->getLevel() > 0)
                    $tierTransaction->sendMailReducedTransactionToAccount($transaction, $reduceCommission, $totalCommission);
            }
        } catch (Exception $e) {
            print_r($e->getMessage());
            die('x');
        }

//        $ratio = $baseReduce / ($baseReduce + $transaction->getCommission());
//        try {
//            $account = Mage::getModel('affiliateplus/account')->setStoreId($transaction->getStoreId());
//            foreach ($tierTransactions as $tierTransaction) {
//                $account->load($tierTransaction->getTierId());
//
//                $balance = $account->getBalance() - ($tierTransaction->getCommission() + $tierTransaction->getCommissionPlus()) * $ratio;
//
//                $reduceCommission = $tierTransaction->getCommission() * $ratio;
//                $totalCommission = ($tierTransaction->getCommission() + $tierTransaction->getCommissionPlus()) * $ratio;
//                if ($tierTransaction->getLevel() == 0) {
//                    $balance += $totalReduce;
//                    $commissionObj->setBaseReduce($reduceCommission);
//                    $commissionObj->setTotalReduce($totalCommission);
//                }
//
//                $tierTransaction->setCommission($tierTransaction->getCommission() * (1 - $ratio))
//                        ->setCommissionPlus($tierTransaction->getCommissionPlus() * (1 - $ratio))
//                        ->save();
//                $account->setBalance($balance)->save();
//
//                // send email to tier
//                if ($tierTransaction->getLevel() > 0)
//                    $tierTransaction->sendMailReducedTransactionToAccount($transaction, $reduceCommission, $totalCommission);
//            }
//        } catch (Exception $e) {
//            
//        }
    }

//    public function reduceTransaction($observer) {
//        /* hainh edit 25-04-2014 */
//        if (!Mage::helper('affiliatepluslevel')->isPluginEnabled()) {
//            return;
//        }
//        $transaction = $observer->getTransaction();
//        $tierTransactions = $this->_getTierTransactions($transaction);
//
//        $commissionObj = $observer->getCommissionObj();
//        $baseReduce = $commissionObj->getBaseReduce();
//        $totalReduce = $commissionObj->getTotalReduce();
//
//        $ratio = $baseReduce / ($baseReduce + $transaction->getCommission());
//        try {
//            $account = Mage::getModel('affiliateplus/account')->setStoreId($transaction->getStoreId());
//            foreach ($tierTransactions as $tierTransaction) {
//                $account->load($tierTransaction->getTierId());
//
//                $balance = $account->getBalance() - ($tierTransaction->getCommission() + $tierTransaction->getCommissionPlus()) * $ratio;
//
//                $reduceCommission = $tierTransaction->getCommission() * $ratio;
//                $totalCommission = ($tierTransaction->getCommission() + $tierTransaction->getCommissionPlus()) * $ratio;
//                if ($tierTransaction->getLevel() == 0) {
//                    $balance += $totalReduce;
//                    $commissionObj->setBaseReduce($reduceCommission);
//                    $commissionObj->setTotalReduce($totalCommission);
//                }
//
//                $tierTransaction->setCommission($tierTransaction->getCommission() * (1 - $ratio))
//                        ->setCommissionPlus($tierTransaction->getCommissionPlus() * (1 - $ratio))
//                        ->save();
//                $account->setBalance($balance)->save();
//
//                // send email to tier
//                if ($tierTransaction->getLevel() > 0)
//                    $tierTransaction->sendMailReducedTransactionToAccount($transaction, $reduceCommission, $totalCommission);
//            }
//        } catch (Exception $e) {
//            
//        }
//    }

    /**
     * 
     * @param type $observer
     * @return type
     */
    public function cancelTransaction($observer) {
        /* hainh edit 25-04-2014 */
        if (!Mage::helper('affiliatepluslevel')->isPluginEnabled()) {
            return;
        }
        $transaction = $observer->getTransaction();
        $transactionStatus = $observer->getStatus();
        $commission = $observer->getCommission();
        $tierTransactions = $this->_getTierTransactions($transaction);

        try {
            $account = Mage::getModel('affiliateplus/account')->setStoreId($transaction->getStoreId());
            foreach ($tierTransactions as $tierTransaction) {

                if ($transactionStatus == 1) {
                    $account->load($tierTransaction->getTierId());

                    if ($tierTransaction->getLevel() == 0) {
                        $balanceAdded = $transaction->getCommission() + $transaction->getCommissionPlus() + $transaction->getCommission() * $transaction->getPercentPlus() / 100;
                        $balance = $account->getData('balance') + $balanceAdded - $tierTransaction->getCommission() - $tierTransaction->getCommissionPlus();
                        //                    $balance = $account->getBalance() + $balanceAdded - $tierTransaction->getCommission() - $tierTransaction->getCommissionPlus();
                    } else {
                        $balance = $account->getData('balance') - $tierTransaction->getCommission() - $tierTransaction->getCommissionPlus();
                        //                    $balance = $account->getBalance() - $tierTransaction->getCommission() - $tierTransaction->getCommissionPlus();
                    }

                    $account->setBalance($balance)
                            ->save();
                }

                $tierTransaction->setCommission(0)
                        ->setCommissionPlus(0)
                        ->save();
                //send mail Complete to account
                if ($tierTransaction->getLevel() > 0)
                    $tierTransaction->sendMailUpdatedTransactionToAccount($transaction, false);
            }
        } catch (Exception $e) {
            
        }
    }

    /**
     * 
     * @param type $transaction
     * @return type
     */
    protected function _getTierTransactions($transaction) {
        /* hainh edit 25-04-2014 */
        if (!Mage::helper('affiliatepluslevel')->isPluginEnabled()) {
            return;
        }
        $collection = Mage::getModel('affiliatepluslevel/transaction')->getCollection()
                ->addFieldToFilter('transaction_id', $transaction->getId())
                ->setOrder('id', 'asc');
        return $collection;
    }

    /**
     * 
     * @param type $observer
     * @return type
     */
    public function joinTransactionToOtherTable($observer) {
        return;
    }

    /**
     * 
     * @param type $observer
     * @return type
     */
    public function addColumnAccountTransactionGrid($observer) {
        /* hainh edit 25-04-2014 */
        if (!Mage::helper('affiliatepluslevel')->isPluginEnabled()) {
            return;
        }
        $grid = $observer->getGrid();
        $grid->addColumn('level', array(
            'header' => Mage::helper('affiliateplus')->__('Level'),
            'width' => '50px',
            'align' => 'right',
            'index' => 'level'
        ));
    }

    /**
     * 
     * @param type $observer
     * @return type
     */
    public function setTransactionCollection($observer) {
        /* hainh edit 25-04-2014 */
        if (!Mage::helper('affiliatepluslevel')->isPluginEnabled()) {
            return;
        }
        $grid = $observer->getGrid();
        $accountId = $observer->getAccountId();
        $storeId = $observer->getStore();

        $transactionTable = Mage::getModel('core/resource')->getTableName('affiliatepluslevel_transaction');
        $collection = Mage::getModel('affiliateplus/transaction')->getCollection();
        $collection->getSelect()
                ->joinLeft(array('ts' => $transactionTable), "ts.transaction_id = main_table.transaction_id", array('level' => 'level'))
                ->columns("if (ts.commission IS NULL, main_table.commission, ts.commission) as commission")
                ->where("ts.tier_id=$accountId OR (ts.tier_id IS NULL AND main_table.account_id = $accountId )");

        if ($storeId)
            $collection->addFieldToFilter('store_id', $storeId);

        $grid->setCollection($collection);
    }

    /**
     * 
     * @param type $observer
     * @return type
     */
    public function addLinkToNavigation($observer) {
        /* hainh edit 25-04-2014 */
        if (!Mage::helper('affiliatepluslevel')->isPluginEnabled()) {
            return;
        }
        $block = $observer->getBlock();
        $link = $observer->getLink();
        if ($link->getName() == 'sales')
            $link->setLabel('Standard Commissions');
    }

    /**
     * 
     * @param type $observer
     * @return type
     */
    public function joinTransactionToOtherTableFrontend($observer) {
        if (!Mage::helper('affiliatepluslevel')->isPluginEnabled()) {
            return;
        }
        $collection = $observer->getCollection();
        $storeId = Mage::app()->getStore()->getId();
        $accountId = Mage::getSingleton('affiliateplus/session')->getAccount()->getAccountId();
        $transactionTable = Mage::getModel('core/resource')->getTableName('affiliatepluslevel_transaction');

        $collection->getSelect()->reset(Zend_Db_Select::WHERE);
        if (Mage::helper('affiliateplus/config')->getSharingConfig('balance', $storeId) == 'store') {
            $collection->addFieldToFilter('store_id', Mage::app()->getStore()->getId());
        } elseif (Mage::helper('affiliateplus/config')->getSharingConfig('balance', $storeId) == 'website') {
            $storeIds = Mage::helper('affiliateplus/account')->getStoreIdsByWebsite();
            $collection->addFieldToFilter('store_id', array('in' => $storeIds));
        }
        $collection->addFieldToFilter('type', '3');
        /* hainh edit 25-04-2014 */

        if (!Mage::helper('affiliatepluslevel')->isPluginEnabled())
            // Changed By Adam (08/09/2015): Doesn't show transaction in frontend if admin disable tier commission plugin from back-end
            $condition = "main_table.tier_id=$accountId";
        else
            $condition = "ts.tier_id=$accountId OR (ts.tier_id IS NULL AND main_table.account_id = $accountId )";

//        Changed By Adam: 19/09/2014: Fix loi khong show transaction o frontend mac du dang co transaction
//        $condition = "ts.tier_id=$accountId OR (ts.tier_id IS NULL AND main_table.account_id = $accountId )";
        $collection->getSelect()
                ->joinLeft(array('ts' => $transactionTable), "ts.transaction_id = main_table.transaction_id", array('level' => 'level'))
                ->columns("if (ts.commission IS NULL, main_table.commission, ts.commission) as commission")
                ->columns("IF (ts.commission_plus IS NULL, main_table.commission_plus, ts.commission_plus) AS commission_plus")
                ->columns("IF (ts.commission_plus IS NULL, main_table.percent_plus, 0) AS percent_plus")
                ->where($condition);
    }

    /**
     * 
     * @param type $observer
     * @return type
     */
    public function resetTransactionCommission($observer) {
        /* hainh edit 25-04-2014 */
        if (!Mage::helper('affiliatepluslevel')->isPluginEnabled()) {
            return;
        }
        $transaction = $observer->getTransaction();
        $topTierTransaction = Mage::getModel('affiliatepluslevel/transaction')->getCollection()
                ->addFieldToFilter('transaction_id', $transaction->getId())
                ->addFieldToFilter('level', 0)
                ->getFirstItem();
        if ($topTierTransaction && $topTierTransaction->getId()) {
            $transaction->setCommission($topTierTransaction->getCommission());
            if (floatval($transaction->getCommissionPlus()) > 0)
                $transaction->setCommissionPlus($topTierTransaction->getCommissionPlus())
                        ->setPercentPlus(0);
        }
    }

    /**
     * 
     * @param type $observer
     * @return type
     */
    public function addTierFieldToProgram($observer) {
        /* hainh edit 25-04-2014 */
        if (!Mage::helper('affiliatepluslevel')->isPluginEnabled()) {
            return;
        }
        $form = $observer->getEvent()->getForm();
        $data = $observer->getEvent()->getFormData();
        $fieldset = $observer->getEvent()->getFieldset();

        // $fieldset = $form->addFieldset('tiers_fieldset',array(
        // 'legend'	=> Mage::helper('affiliatepluslevel')->__('Tier Commission')
        // ));

        $inStore = Mage::app()->getRequest()->getParam('store');
        $defaultLabel = Mage::helper('affiliateplusprogram')->__('Use Default');
        $defaultTitle = Mage::helper('affiliateplusprogram')->__('-- Please Select --');
        $scopeLabel = Mage::helper('affiliateplusprogram')->__('STORE VIEW');

        $fieldset->addField('multilevel_separator', 'text', array(
            'label' => Mage::helper('affiliatepluslevel')->__('Tier Commission'),
            'comment' => '10px',
        ))->setRenderer(Mage::app()->getLayout()->createBlock('affiliateplus/adminhtml_field_separator'));

        $inStoreData = isset($data['use_tier_config_in_store']) ? $data['use_tier_config_in_store'] : false;
        $fieldset->addField('use_tier_config', 'select', array(
            'name' => 'use_tier_config',
            'label' => Mage::helper('affiliatepluslevel')->__('Use General Configuration'),
            'title' => Mage::helper('affiliatepluslevel')->__('Use General Configuration'),
            'values' => Mage::getSingleton('adminhtml/system_config_source_yesno')->toOptionArray(),
            'disabled' => ($inStore && !$inStoreData),
            'after_element_html' => ($inStore ? '</td><td class="use-default">
			<input id="use_tier_config_default" name="use_tier_config_default" type="checkbox" value="1" class="checkbox config-inherit" ' . ($inStoreData ? '' : 'checked="checked"') . ' onclick="toggleValueElements(this, Element.previous(this.parentNode))" />
			<label for="use_tier_config_default" class="inherit" title="' . $defaultTitle . '">' . $defaultLabel . '</label>
          </td>' : '') . '</td><td class="scope-label">
			[' . $scopeLabel . ']<script type="text/javascript">
				function changeTierConfig(){
					var config = $(\'affiliateplusprogram_use_tier_config\').value;
					if (config == 1){
						$(\'affiliateplusprogram_max_level\').parentNode.parentNode.hide();
						$(\'grid_tier_commission\').parentNode.parentNode.hide();
						$(\'grid_sec_tier_commission\').parentNode.parentNode.hide();
						$(\'affiliateplusprogram_use_sec_tier\').parentNode.parentNode.hide();
					}else{
						$(\'affiliateplusprogram_max_level\').parentNode.parentNode.show();
						$(\'grid_tier_commission\').parentNode.parentNode.show();
						$(\'affiliateplusprogram_use_sec_tier\').parentNode.parentNode.show();
                        changeSecTierConfig();
					}
				}
                function changeSecTierConfig() {
                    if ($(\'affiliateplusprogram_use_sec_tier\').value == 1) {
                        $(\'grid_sec_tier_commission\').parentNode.parentNode.show();
                    } else {
                        $(\'grid_sec_tier_commission\').parentNode.parentNode.hide();
                    }
                }
				Event.observe(window,\'load\',changeTierConfig);
			</script>',
            'onchange' => 'changeTierConfig()'
        ));
        $inStoreData = isset($data['max_level_in_store']) ? $data['max_level_in_store'] : false;
        $fieldset->addField('max_level', 'text', array(
            'name' => 'max_level',
            'label' => Mage::helper('affiliatepluslevel')->__('Number of Tiers to Enable'),
            'title' => Mage::helper('affiliatepluslevel')->__('Number of Tiers to Enable'),
            'disabled' => ($inStore && !$inStoreData),
            'after_element_html' => $inStore ? '</td><td class="use-default">
			<input id="max_level_default" name="max_level_default" type="checkbox" value="1" class="checkbox config-inherit" ' . ($inStoreData ? '' : 'checked="checked"') . ' onclick="toggleValueElements(this, Element.previous(this.parentNode))" />
			<label for="max_level_default" class="inherit" title="' . $defaultTitle . '">' . $defaultLabel . '</label>
          </td><td class="scope-label">
			[' . $scopeLabel . ']
          ' : '</td><td class="scope-label">
			[' . $scopeLabel . ']',
        ));
        $inStoreData = isset($data['tier_commission_in_store']) ? $data['tier_commission_in_store'] : false;
        $fieldset->addField('tier_commission', 'text', array(
            'name' => 'tier_commission',
            'label' => Mage::helper('affiliatepluslevel')->__('Tier Commission Value & Type'),
            'title' => Mage::helper('affiliatepluslevel')->__('Tier Commission Value & Type'),
            'disabled' => ($inStore && !$inStoreData),
            'after_element_html' => $inStore ? '</td><td class="use-default">
			<input id="tier_commission_default" name="tier_commission_default" type="checkbox" value="1" class="checkbox config-inherit" ' . ($inStoreData ? '' : 'checked="checked"') . ' onclick="toggleValueElements(this, Element.previous(this.parentNode))" />
			<label for="tier_commission_default" class="inherit" title="' . $defaultTitle . '">' . $defaultLabel . '</label>
          </td><td class="scope-label">
			[' . $scopeLabel . ']
          ' : '</td><td class="scope-label">
			[' . $scopeLabel . ']',
        ))->setRenderer(Mage::app()->getLayout()->createBlock('affiliatepluslevel/adminhtml_program_tier')->setProgramData($data));

        $inStoreData = isset($data['use_sec_tier_in_store']) ? $data['use_sec_tier_in_store'] : false;
        $fieldset->addField('use_sec_tier', 'select', array(
            'name' => 'use_sec_tier',
            'label' => Mage::helper('affiliatepluslevel')->__('Use different commission from 2nd order of a Customer'),
            'title' => Mage::helper('affiliatepluslevel')->__('Use different commission from 2nd order of a Customer'),
            'values' => Mage::getSingleton('adminhtml/system_config_source_yesno')->toOptionArray(),
            'disabled' => ($inStore && !$inStoreData),
            'after_element_html' => '<p class="note">' . Mage::helper('affiliatepluslevel')->__('Select "No" to apply above commission for all orders') . '</p>' .
            ($inStore ? '</td><td class="use-default">
			<input id="use_sec_tier_default" name="use_sec_tier_default" type="checkbox" value="1" class="checkbox config-inherit" ' . ($inStoreData ? '' : 'checked="checked"') . ' onclick="toggleValueElements(this, Element.previous(this.parentNode))" />
			<label for="use_sec_tier_default" class="inherit" title="' . $defaultTitle . '">' . $defaultLabel . '</label>
          </td>' : '') . '</td><td class="scope-label">
			[' . $scopeLabel . ']',
            'onchange' => 'changeSecTierConfig()'
        ));

        $inStoreData = isset($data['sec_tier_commission_in_store']) ? $data['sec_tier_commission_in_store'] : false;
        $fieldset->addField('sec_tier_commission', 'text', array(
            'name' => 'sec_tier_commission',
            'label' => Mage::helper('affiliatepluslevel')->__('Tier Commission Value & Type (from 2nd order)'),
            'title' => Mage::helper('affiliatepluslevel')->__('Tier Commission Value & Type (from 2nd order)'),
            'disabled' => ($inStore && !$inStoreData),
            'after_element_html' => $inStore ? '</td><td class="use-default">
			<input id="sec_tier_commission_default" name="sec_tier_commission_default" type="checkbox" value="1" class="checkbox config-inherit" ' . ($inStoreData ? '' : 'checked="checked"') . ' onclick="toggleValueElements(this, Element.previous(this.parentNode))" />
			<label for="sec_tier_commission_default" class="inherit" title="' . $defaultTitle . '">' . $defaultLabel . '</label>
          </td><td class="scope-label">
			[' . $scopeLabel . ']
          ' : '</td><td class="scope-label">
			[' . $scopeLabel . ']',
        ))->setRenderer(Mage::app()->getLayout()->createBlock('affiliatepluslevel/adminhtml_program_sectier')->setProgramData($data));
    }

    /**
     * Changed By Adam to solve the problem of refund tung phan 25/08/2014
     * @param type $observer
     * @return type
     */
    public function update_tiercommission_to_tieraffiliate_partial_refund($observer) {
        /* hainh edit 25-04-2014 */
        if (!Mage::helper('affiliatepluslevel')->isPluginEnabled()) {
            return;
        }

        $creditmemoItem = $observer->getCreditmemoItem();
        $transaction = $observer->getTransaction();
        $orderItem = $creditmemoItem->getOrderItem();
        $affiliateplusCommissionItem = explode(",", $orderItem->getAffiliateplusCommissionItem());

        $tierTransactions = $this->_getTierTransactions($transaction);

        try {
            $account = Mage::getModel('affiliateplus/account')->setStoreId($transaction->getStoreId());
            foreach ($tierTransactions as $tierTransaction) {
                if ($tierTransaction->getLevel()) {
                    $totalCommissionItemLevel = $affiliateplusCommissionItem[$tierTransaction->getLevel()];
                    $commission = $totalCommissionItemLevel * $creditmemoItem->getQty() / $orderItem->getQtyOrdered();

                    $account->load($tierTransaction->getTierId());
                    if ($account->getData('balance') <= $commission)
                        $account->setBalance(0)->save();
                    else
                        $account->setBalance($account->getData('balance') - $commission)->save();
//                    $account->setBalance($account->getBalance() - $commission)->save();
                }
            }
        } catch (Exception $e) {
            print_r($e->getMessage());
            die('d');
        }
    }
    
    /**
     * Changed By Adam (28/12/2015: fix issue of wrong commission when have multiple levels transaction
     * In referrer page, it calculates total commission by transaction that is total tier commissions. It should be calculate commission of each affiliate.
     * @param type $observer
     * @return type
     */
    public function affiliateplus_referrer_sales_collection($observer) {
        if (!Mage::helper('affiliatepluslevel')->isPluginEnabled()) {
            return;
        }
        $collection = $observer->getCollection();
        $storeId = Mage::app()->getStore()->getId();
        $accountId = Mage::getSingleton('affiliateplus/session')->getAccount()->getAccountId();
        $transactionTable = Mage::getModel('core/resource')->getTableName('affiliatepluslevel_transaction');

        $collection->getSelect()->reset(Zend_Db_Select::WHERE);
        if (Mage::helper('affiliateplus/config')->getSharingConfig('balance', $storeId) == 'store') {
            $collection->addFieldToFilter('store_id', Mage::app()->getStore()->getId());
        } elseif (Mage::helper('affiliateplus/config')->getSharingConfig('balance', $storeId) == 'website') {
            $storeIds = Mage::helper('affiliateplus/account')->getStoreIdsByWebsite();
            $collection->addFieldToFilter('store_id', array('in' => $storeIds));
        }
        // $collection->addFieldToFilter('type', '3');
        /* hainh edit 25-04-2014 */

        if (!Mage::helper('affiliatepluslevel')->isPluginEnabled())
            // Changed By Adam (08/09/2015): Doesn't show transaction in frontend if admin disable tier commission plugin from back-end
            $condition = "main_table.tier_id=$accountId";
        else
            $condition = "ts.tier_id=$accountId OR (ts.tier_id IS NULL AND main_table.account_id = $accountId )";

//        Changed By Adam: 19/09/2014: Fix loi khong show transaction o frontend mac du dang co transaction
//        $condition = "ts.tier_id=$accountId OR (ts.tier_id IS NULL AND main_table.account_id = $accountId )";
        $collection->getSelect()
                ->joinLeft(array('ts' => $transactionTable), "ts.transaction_id = main_table.transaction_id", array('level' => 'level'))
                ->columns("if (ts.commission IS NULL, main_table.commission, ts.commission) as commission")
                ->columns("IF (ts.commission_plus IS NULL, main_table.commission_plus, ts.commission_plus) AS commission_plus")
                ->columns("IF (ts.commission_plus IS NULL, main_table.percent_plus, 0) AS percent_plus")
                ->where($condition);
    }

}
