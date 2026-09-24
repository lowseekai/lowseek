# Point Rewards

This fork is adapted for Flarum 2 and uses `ramon/point-system` as its points provider.

Users can transfer integer points to other users by rewarding their posts. The
reward history is available from the user profile.

## Installation

```sh
composer require lowseekai/flarum-ext-money-rewards
php flarum migrate
php flarum cache:clear
```

The `money-rewards.*` permission names are retained for compatibility with the
original extension. The `amount` field is now an integer point amount.
