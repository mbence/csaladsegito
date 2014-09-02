Családsegítő Admin / Family Help Admin
========================================

Családsegítő és Gyermekjóléti szolgálatok adminisztrációs rendszere /
Administration system of family help and child welfare services

## Requirements

* PHP 5.5
* Symfony 2.2
* Node.js / Coffeescript

To recompile the coffee assets during development, run the assetic compiler:

```
php app/console assetic:watch
```

### Mysql fulltext search settings
If you want three-character words to be searchable, you can set the ft_min_word_len variable by putting the following lines in an option file:
```
[mysqld]
ft_min_word_len=3
```

FULLTEXT indexes must be rebuilt after changing this variable. Use `REPAIR TABLE client QUICK`.

### Xdebug
In case you use xdebug for the development it is necessary to raise the function nesting level to be able to render the forms:

In php.ini:
```
[xdebug]
xdebug.max_nesting_level = 1000
```