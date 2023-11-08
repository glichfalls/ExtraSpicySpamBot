<?php

namespace App\Service\Telegram\Button;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @method TelegramButton[] getValues()
 */
class TelegramKeyboard extends ArrayCollection
{

    /**
     * @return array<TelegramButton[]>
     */
    public function getRows(int $numberOfCols): array
    {
        $rows = [];
        $row = [];
        foreach ($this->getValues() as $button) {
            $row[] = $button->toArray();
            if (count($row) === $numberOfCols) {
                $rows[] = $row;
                $row = [];
            }
        }
        if (count($row) > 0) {
            $rows[] = $row;
        }
        return $rows;
    }

}
