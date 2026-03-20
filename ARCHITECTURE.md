# Architecture: ps_wirepayment

## Purpose

A PrestaShop payment module that implements bank wire (offline payment) processing. Customers choose bank wire at checkout, receive the merchant's bank details, and the order stays pending until payment is confirmed manually.

## Directory Structure

```
ps_wirepayment.php                  - Module class; config, hook listeners, payment option
controllers/front/payment.php       - Front controller: renders payment instructions after checkout
controllers/front/validation.php    - Front controller: handles order validation/confirmation
views/templates/hook/               - Smarty templates for checkout and order-confirmation display
upgrade/                            - SQL/PHP migration scripts
translations/                       - Locale string overrides
```

## Key Design Decisions

- **Offline payment flow**: No real-time payment gateway — order is created with status "awaiting bank wire payment" and must be manually marked as paid by the merchant.
- **Configurable bank details**: Account owner, bank name, address, and account number are stored as `Configuration` values and displayed to customers post-checkout.
- **PaymentModule extension**: Extends PrestaShop's `PaymentModule` base class which handles order creation and status assignment.

## Extension Points

- Add validation logic in `hookPaymentReturn()` to auto-confirm orders from known accounts.
- Add multi-bank-account support by storing accounts as a JSON `Configuration` value.

## Dependency Flow

```
ps_wirepayment (PaymentModule)
  └─> hookPaymentOptions()     — registers the "Bank Wire" payment option at checkout
  └─> hookPaymentReturn()      — renders confirmation page with bank details
  └─> hookDisplayOrderDetail() — shows bank details on the order history page
  └─> controllers/front/validation.php — creates the PrestaShop order record
```
