<?php

declare(strict_types=1);

/**
 * Example: Working with the ps_wirepayment PrestaShop module.
 *
 * ps_wirepayment adds a "Pay by Bank Wire" (bank transfer) payment option
 * to checkout. Orders are placed immediately but payment is marked as pending
 * until the merchant manually confirms receipt of the transfer.
 *
 * This file documents common integration patterns.
 */

// --- The module implements PaymentModule ---
// It is automatically rendered on the checkout payment step.
// No direct PHP call needed for standard use.

// --- Back Office configuration ---
// Modules > Bank Wire:
//   - Account owner name
//   - Account details (IBAN / account number)
//   - Bank address
//   - BIC/SWIFT code
//   - Order status after bank wire selected (default: "Awaiting bank wire payment")

// --- Hook: paymentOptions ---
// The module registers a PaymentOption with bank account details in the
// additional info section shown to the customer after selecting this method.

// --- Confirming a bank wire order ---
// After the bank transfer arrives, the merchant transitions the order:
//   Back Office > Orders > {Order} > Change Status > "Payment Accepted"
//
// Programmatic status change:
// $order   = new Order($orderId);
// $history = new OrderHistory();
// $history->id_order = $order->id;
// $history->changeIdOrderState(_PS_OS_PAYMENT_, $order, true);
// $history->addWithemail();

// --- Checking if an order used bank wire ---
// $order    = new Order($orderId);
// $isWire   = ($order->module === 'ps_wirepayment');

// --- Template override ---
// themes/{theme}/modules/ps_wirepayment/views/templates/front/payment_execution.tpl
// themes/{theme}/modules/ps_wirepayment/views/templates/front/payment_return.tpl
