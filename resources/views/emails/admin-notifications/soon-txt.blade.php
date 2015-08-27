@extends('emails.base.base-txt')

@section('main')

Your Swapbot Will Expire In A Week


Hi {{ $user['name'] }}.

Your Swapbot named {{ $bot['name'] }} will expire in a week.  After that, this Swapbot will not be able to process any swaps.

Please vist our Swapbot administration app at {{ $adminUrl }} and follow the instructions there to make a payment before this Swapbot expires.


If you have any questions or comments about your experience please email the team@tokenly.com.


To stop receiving these notifications, please update your email preferences at {{ $updateEmailPrefsLink }}.


@stop
