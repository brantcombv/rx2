<?php

class Rx_Form_Decorator_Errors extends Zend_Form_Decorator_Abstract
{
    /**
     * Render errors
     *
     * @param  string $content
     * @return string
     */
    public function render($content)
    {
        $element = $this->getElement();
        $view = $element->getView();
        if (null === $view) {
            return $content;
        }

        $errors = $element->getMessages();
        if (empty($errors)) {
            return $content;
        }

        // Workaround for bug ZF-9753
        if (is_array($errors)) {
            $_errors = $errors;
            $errors = array();
            foreach ($_errors as $item) {
                if (is_array($item)) {
                    $errors = array_merge($errors, $item);
                } else {
                    $errors[] = $item;
                }
            }
            if (!sizeof($errors)) {
                return $content;
            }
        }

        $separator = $this->getSeparator();
        $placement = $this->getPlacement();
        $errors = $view->formErrors($errors, $this->getOptions());

        if (!strlen($errors)) {
            return $content;
        }

        switch ($placement) {
            case self::APPEND:
                return $content . $separator . $errors;
            case self::PREPEND:
                return $errors . $separator . $content;
        }
    }
}