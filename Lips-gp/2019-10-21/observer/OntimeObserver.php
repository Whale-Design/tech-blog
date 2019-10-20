<?php
namespace Observer;

use SplObserver;
use SplSubject;

class OntimeObserver implements SplObserver
{

    /**
     * Receive update from subject
     * @link http://php.net/manual/en/splobserver.update.php
     * @param SplSubject $subject <p>
     * The <b>SplSubject</b> notifying the observer of an update.
     * </p>
     * @return string
     * @since 5.1.0
     */
    public function update(SplSubject $subject)
    {
        echo '我是观察者2：记录用户在线时间' .'<br>';
    }
}