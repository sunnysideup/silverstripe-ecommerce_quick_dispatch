<?php


class Order_QuickDispatch extends DataExtension
{
    /**
     * @var bool
     */
    private static $remove_parent_log_field = false;

    private static $db = array(
        'HasBeenDispatched' => 'Boolean'
    );

    private static $field_labels = array(
        'HasBeenDispatched' => 'Has been dispatched'
    );

    /**
     * Update Fields
     * @return FieldList
     */
    public function updateCMSFields(FieldList $fields)
    {
        $currentStatus = $this->owner->Status();
        //start hack
        if($currentStatus && $currentStatus->ClassName === 'OrderStep_Sent') {
            $orderStepGood = OrderStep::get()->filter(array('Code' => 'OrderStep_QuickDispatch'))->first();
            if($orderStepGood && $this->owner->StatusID != $orderStepGood->ID) {
                $orderStepBad = OrderStep::get()->filter(array('Code' => 'OrderStep_Sent'))->first();
                if($orderStepBad && $this->owner->StatusID == $orderStepBad->ID) {
                    $this->owner->StatusID = $orderStepGood->ID;
                    $this->owner->write();
                }
            }
        }
        //end hack
        if($currentStatus && $currentStatus instanceof OrderStep_QuickDispatch) {
            $allFields = Config::inst()->get('Order_QuickDispatch', 'remove_parent_log_field') ? false : true;
            if($allFields) {
                $headerField1 = HeaderField::create('QuickDispatchHeader1', ' - Option A: Quick Dispatch', 5);
            } else {
                $headerField1 = HiddenField::create('QuickDispatchHeader1');
            }
            $checkboxfield = OptionSetField::create(
                'HasBeenDispatched',
                'Has been dispatched',
                array(
                    0 => _t('Order_QuickDispatch.NOT_YET', 'Not yet'),
                    1 => _t('Order_QuickDispatch.GONE', 'It\'s Gone')
                )
            );
            if(class_exists('DataObjectOneFieldUpdateController')) {
                $link = DataObjectOneFieldUpdateController::popup_link(
                    $ClassName = 'Order',
                    $FieldName = 'HasBeenDispatched',
                    $where = 'StatusID = '.$this->owner->StatusID,
                    $sort = 'ID',
                    $linkText = 'Batch Dispatch',
                    $titleField = "Title"
                );
                $checkboxfield->setDescription('You can also do a: '.$link);
            }
            if($allFields) {
                $headerField2 = HeaderField::create('QuickDispatchHeader2', ' - Option B: Detailed Dispatch', 5);
            } else {
                $headerField2 = HiddenField::create('QuickDispatchHeader2');
            }
            $fields->addFieldsToTab(
                'Root.Next',
                array(
                    $headerField1,
                    $checkboxfield,
                    $headerField2
                ),
                'OrderStatusLog_DispatchPhysicalOrder'
            );
            if(! $allFields) {
                $fields->removeFieldFromTab(
                    'Root.Next',
                    'OrderStatusLog_DispatchPhysicalOrder'
                );
            }
        } else {
            $fields->removeByName('HasBeenDispatched');
        }
    }

    private static $_on_after_write_count_for_order_step_dispatch = array();

    /**
     * Event handler called after writing to the database.
     */
    public function onAfterWrite()
    {
        $currentStatus = $this->owner->Status();
        if($currentStatus && $currentStatus instanceof OrderStep_QuickDispatch) {
            if(!isset(self::$_on_after_write_count_for_order_step_dispatch[$this->owner->ID])) {
                self::$_on_after_write_count_for_order_step_dispatch[$this->owner->ID] = 0;
            }
            if(self::$_on_after_write_count_for_order_step_dispatch[$this->owner->ID] < 2) {
                self::$_on_after_write_count_for_order_step_dispatch[$this->owner->ID]++;
                $this->owner->tryToFinaliseOrder();
            }
        }
    }

}
