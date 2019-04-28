<?php
/**
 * This file is part of a Lyssal project.
 *
 * @copyright Rémi Leclerc
 * @author Rémi Leclerc
 */
namespace Lyssal\Doctrine\Orm\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * To add a updatedAt property in an entity.
 *
 * Do not forget to add the `HasLifecycleCallbacks` annotation in your entity.
 *
 * @deprecated Please find this functionality in the Lyssal entity bundle lyssal/entity-bundle
 *
 * @ORM\HasLifecycleCallbacks()
 */
trait UpdatedAtTrait
{
    /**
     * The update date
     *
     * @var \DateTimeInterface
     *
     * @ORM\Column(type="datetime", nullable=false, options={"default"="CURRENT_TIMESTAMP"})
     */
    protected $updatedAt;


    /**
     * @return \DateTimeInterface
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param \DateTimeInterface $updatedAt
     */
    public function setUpdatedAt(DateTimeInterface $updatedAt)
    {
        $this->updatedAt = $updatedAt;
    }


    /**
     * Init the update date.
     *
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function initUpdatedAt()
    {
        $this->updatedAt = new DateTime();
    }
}
