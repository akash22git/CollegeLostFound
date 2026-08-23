<?php

function isValidReportDate(string $date): bool
{
    $dateObject = DateTime::createFromFormat('!Y-m-d', $date);
    $errors = DateTime::getLastErrors();

    if (
        $dateObject === false
        || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
        || $dateObject->format('Y-m-d') !== $date
    ) {
        return false;
    }

    return $dateObject <= new DateTime('today');
}
