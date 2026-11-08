<?php

class Ps_WirepaymentValidateOrderSpy extends Ps_Wirepayment
{
    public function validateOrder(
        $id_cart,
        $id_order_state,
        $amount,
        $payment_method,
        $message,
        $extra_vars,
        $currency_special,
        $dont_touch_amount,
        $secure_key
    ) {
        parent::validateOrder(
            $id_cart,
            $id_order_state,
            $amount,
            $payment_method,
            $message,
            $extra_vars,
            $currency_special,
            $dont_touch_amount,
            $secure_key
        );
    }
}
