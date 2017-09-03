<?php

interface Rx_Notify_Observer
{
    /**
     * Handle given notification event
     *
     * @param Rx_Notify_Event $event Notification event object
     * @return void
     */
    public function handleNotify($event);

}
