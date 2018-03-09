<?php



class OrderStep_QuickDispatch extends OrderStep_Sent implements OrderStepInterface
{

    private static $defaults = array(
        'CustomerCanEdit' => 0,
        'CustomerCanPay' => 0,
        'CustomerCanCancel' => 0,
        'Name' => 'Dispatch',
        'Code' => 'QUICK_DISPATCH',
        'ShowAsInProcessOrder' => 1,
    );


    /**
     * Can run this step once any items have been submitted.
     * makes sure the step is ready to run.... (e.g. check if the order is ready to be emailed as receipt).
     * should be able to run this function many times to check if the step is ready.
     *
     * @see Order::doNextStatus
     *
     * @param Order object
     *
     * @return bool - true if the current step is ready to be run...
     **/
    public function initStep(Order $order)
    {
        return parent::initStep($order);
    }

     /**
      * Add a member to the order - in case he / she is not a shop admin.
      *
      * @param Order object
      *
      * @return bool - true if run correctly.
      **/
     public function doStep(Order $order)
     {
         if($order->HasBeenDispatched) {
            if ( ! $this->RelevantLogEntry($order)) {
                 $log = OrderStatusLog_DispatchPhysicalOrder::create();
                 $log->OrderID = $order->ID;
                 $log->write();
            }
         }
         return parent::doStep($order);
     }

    /**
     * go to next step if order has been submitted.
     *
     * @param Order $order
     *
     * @return OrderStep | Null	(next step OrderStep)
     **/
    public function nextStep(Order $order)
    {
        return parent::nextStep($order);
    }


    /**
     * Explains the current order step.
     *
     * @return string
     */
    protected function myDescription()
    {
        return _t('OrderStep.QUICKDISPATCH_DESCRIPTION', 'The order is on its way to the customer.');
    }



}
