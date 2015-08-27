@extends('emails.base.base-txt')

@section('main')

Your Swapbot Payment is Past Due


Hi {{ $user['name'] }}.

Your Swapbot named {{ $bot['name'] }} is now unpaid and is not processing any swaps.

To fix this, please vist our Swapbot administration app at {{ $adminUrl }} and follow the instructions there to make a payment.


If you have any questions or comments about your experience please email the team@tokenly.com.


To stop receiving these notifications, please update your email preferences at {{ $updateEmailPrefsLink }}.


@stop