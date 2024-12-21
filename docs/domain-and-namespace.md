# Domain and namespace

[Back to README](./../README.md)

## Table of Contents

- [Directory structure](#directory-structure)
- [Specify the name of the domain](#specify-the-name-of-the-domain)
- [Specify the namespace](#specify-the-namespace)
- [Notes and limitations](#notes-and-limitations)

## Directory structure

[⬆️ Go to TOC](#table-of-contents)

The directory structure of a domain is as follows:

```
app/
├── <Namespace>/
│   └── <Domain>/
│       ├── Actions/
│       │   ├── Create<Model>
│       │   ├── Delete<Model>
│       │   └── etc.
│       └── etc.
└── etc.
```

By default, the **namespace** (or root folder) is `Domain`.

The name of the **domain** can be the same of the name of the **model**, or different.

E.g., for model `Animal`:

```
app/
├── Domain/
│   └── Animal/
│       ├── Actions/
│       │   ├── CreateAnimal
│       │   ├── DeleteAnimal
│       │   └── etc.
│       └── etc.
└── etc.
```

## Specify the name of the domain

[⬆️ Go to TOC](#table-of-contents)

It is possible to specify a different domain name by answering the interactive question, or by using the option
`--domain`.

This allows sharing the same domain for different models.

### Answering the question

```shell
php artisan make:event-sourcing-domain Tiger
```

```
Which is the name of the domain? [Tiger]
> Animal

... etc.
```

```shell
php artisan make:event-sourcing-domain Lion
```

```
Which is the name of the domain? [Lion]
> Animal

... etc.
```

### Using command line option

```shell
php artisan make:event-sourcing-domain Animal --domain=Tiger
php artisan make:event-sourcing-domain Animal --domain=Lion
```

If specified as option, the name of the domain will not be asked.

### Result

Result of both approaches:

```
app/
├── Domain/
│   └── Animal/
│       ├── Actions/
│       │   ├── CreateLion
│       │   ├── CreateTiger
│       │   ├── DeleteLion
│       │   ├── DeleteTiger
│       │   └── etc.
│       └── etc.
└── etc.
```

## Specify the namespace

[⬆️ Go to TOC](#table-of-contents)

It is possible to specify a different namespace using option `--namespace`.

```shell
php artisan make:event-sourcing-domain Tiger --namespace=MyDomain --domain=Animal
```

Result:

```
app/
├── MyDomain/
│   └── Animal/
│       ├── Actions/
│       │   ├── CreateTiger
│       │   ├── DeleteTiger
│       │   └── etc.
│       └── etc.
└── etc.
```

## Notes and limitations

[⬆️ Go to TOC](#table-of-contents)

[Reserved PHP words](https://www.php.net/manual/en/reserved.keywords.php) cannot be used as namespace or domain.

### Examples

Namespace example:

```shell
php artisan make:event-sourcing-domain Tiger --namespace=Array --domain=Animal
```

```
ERROR  The namespace Array is reserved by PHP.
```

Domain example:

```shell
php artisan make:event-sourcing-domain Tiger --domain=Echo
```

```
ERROR  The domain Echo is reserved by PHP.
```