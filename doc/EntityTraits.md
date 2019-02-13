# Traits for Entities


## CreatedAt

The `CreatedAtTrait` add a `createdAt` property in the entity which is initialized when the entity is persisted.

```php
use Doctrine\ORM\Mapping as ORM;
use Lyssal\Doctrine\Orm\Entity\CreatedAtTrait;

/**
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 */
class MyEntity
{
    use CreatedAtTrait;
    
    // ...
}
```

Do not forget to add the `HasLifecycleCallbacks` annotation in your entity.


## UpdatedAt

The `UpdatedAtTrait` add a `updatedAt` property in the entity which is initialized when the entity is persisted or updated.

```php
use Doctrine\ORM\Mapping as ORM;
use Lyssal\Doctrine\Orm\Entity\UpdatedAtTrait;

/**
 * @ORM\Entity()
 * @ORM\HasLifecycleCallbacks()
 */
class MyEntity
{
    use UpdatedAtTrait;
    
    // ...
}
```

Do not forget to add the `HasLifecycleCallbacks` annotation in your entity.