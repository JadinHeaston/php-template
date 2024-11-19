<?php

/**
 * Custom Auth function that should return whether the user is authorized or not.
 * A failed auth results in reauth() being called.
 *
 * @return boolean
 */
function auth(): bool
{
	return true;
}

/**
 * Custom function that is triggered upon a failed auth().
 *
 * @return boolean
 */
function reauth(): void
{
	exit(1);
}
