<?php

namespace spec\Knp\RadBundle\Doctrine\Listener;

use PHPSpec2\ObjectBehavior;
use Doctrine\ORM\Events;

class PasswordHashListener extends ObjectBehavior
{
    /**
     * @param Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface  $encoderFactory
     * @param Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface $encoder
     */
    function let($encoderFactory, $encoder)
    {
        $encoderFactory->getEncoder(ANY_ARGUMENT)->willReturn($encoder);
        $encoder->encodePassword(ANY_ARGUMENTS)->willReturnUsing(function($password, $salt) {
            return $password.'#'.$salt;
        });

        $this->beConstructedWith($encoderFactory);
    }

    function it_should_be_doctrine2_event_subscriber()
    {
        $this->shouldBeAnInstanceOf('Doctrine\Common\EventSubscriber');
    }

    function it_should_support_persist_and_update_events()
    {
        $this->getSubscribedEvents()->shouldReturn(array(
            Events::prePersist,
            Events::preUpdate,
        ));
    }

    /**
     * @param Doctrine\ORM\Event\LifecycleEventArgs $args
     * @param Knp\RadBundle\Security\UserInterface  $entity
     */
    function its_prePersist_should_rehash_user_password_if_new_password_providen($args, $entity)
    {
        $args->getEntity()->willReturn($entity);

        $entity->getPlainPassword()->willReturn('custom_pass');
        $entity->getSalt()->willReturn('some_salt');
        $entity->setPassword('custom_pass#some_salt')->shouldBeCalled();
        $entity->eraseCredentials()->shouldBeCalled();

        $this->prePersist($args);
    }

    /**
     * @param Doctrine\ORM\Event\LifecycleEventArgs           $args
     * @param Knp\RadBundle\Security\RecoverableUserInterface $entity
     */
    function its_prePersist_should_erase_password_recovery_key_for_recoverable_user($args, $entity)
    {
        $args->getEntity()->willReturn($entity);

        $entity->getPlainPassword()->willReturn('custom_pass');
        $entity->getSalt()->willReturn('some_salt');

        $entity->erasePasswordRecoveryKey()->shouldBeCalled();

        $this->prePersist($args);
    }

    /**
     * @param Doctrine\ORM\Event\LifecycleEventArgs $args
     * @param Knp\RadBundle\Security\UserInterface  $entity
     */
    function its_prePersist_should_not_touch_entity_if_no_new_password_providen($args, $entity)
    {
        $args->getEntity()->willReturn($entity);

        $entity->getPlainPassword()->willReturn(null);
        $entity->getSalt()->willReturn('some_salt');
        $entity->setPassword(ANY_ARGUMENT)->shouldNotBeCalled();

        $this->prePersist($args);
    }

    /**
     * @param Doctrine\ORM\Event\LifecycleEventArgs $args
     * @param stdClass                              $entity
     */
    function its_prePersist_should_not_touch_entities_without_interface($args, $entity)
    {
        $args->getEntity()->willReturn($entity);
        $entity->setPassword(ANY_ARGUMENT)->shouldNotBeCalled();

        $this->prePersist($args);
    }

    /**
     * @param Doctrine\ORM\Event\PreUpdateEventArgs     $args
     * @param Knp\RadBundle\Security\UserInterface      $entity
     * @param Doctrine\Common\Persistence\ObjectManager $em
     * @param Doctrine\ORM\UnitOfWork                   $uow
     * @param Doctrine\ORM\Mapping\ClassMetadata        $meta
     */
    function its_preUpdate_should_rehash_user_password_if_new_password_providen($args, $entity,
                                                                                $em, $uow, $meta)
    {
        $args->getEntity()->willReturn($entity);
        $args->getEntityManager()->willReturn($em);

        $em->getUnitOfWork()->willReturn($uow);
        $em->getClassMetadata(get_class($entity->getWrappedSubject()))->willReturn($meta);

        $entity->getPlainPassword()->willReturn('custom_pass');
        $entity->getSalt()->willReturn('some_salt');

        $entity->setPassword('custom_pass#some_salt')->shouldBeCalled();
        $entity->eraseCredentials()->shouldBeCalled();
        $uow->recomputeSingleEntityChangeSet($meta, $entity)->shouldBeCalled();

        $this->preUpdate($args);
    }

    /**
     * @param Doctrine\ORM\Event\PreUpdateEventArgs           $args
     * @param Knp\RadBundle\Security\RecoverableUserInterface $entity
     * @param Doctrine\Common\Persistence\ObjectManager       $em
     * @param Doctrine\ORM\UnitOfWork                         $uow
     * @param Doctrine\ORM\Mapping\ClassMetadata              $meta
     */
    function its_preUpdate_should_erase_password_recovery_key_for_recoverable_user($args, $entity,
                                                                                   $em, $uow, $meta)
    {
        $args->getEntity()->willReturn($entity);
        $args->getEntityManager()->willReturn($em);

        $em->getUnitOfWork()->willReturn($uow);
        $em->getClassMetadata(get_class($entity->getWrappedSubject()))->willReturn($meta);

        $entity->getPlainPassword()->willReturn('custom_pass');
        $entity->getSalt()->willReturn('some_salt');

        $entity->erasePasswordRecoveryKey()->shouldBeCalled();

        $this->preUpdate($args);
    }

    /**
     * @param Doctrine\ORM\Event\PreUpdateEventArgs $args
     * @param Knp\RadBundle\Security\UserInterface  $entity
     */
    function its_preUpdate_should_not_touch_entity_if_password_is_not_updated($args, $entity)
    {
        $args->getEntity()->willReturn($entity);
        $args->getEntityManager()->shouldNotBeCalled();

        $entity->getPlainPassword()->willReturn(null);
        $entity->setPassword(ANY_ARGUMENT)->shouldNotBeCalled();

        $this->preUpdate($args);
    }

    /**
     * @param Doctrine\ORM\Event\PreUpdateEventArgs $args
     * @param stdClass                              $entity
     */
    function its_preUpdate_should_not_touch_entities_without_interface($args, $entity)
    {
        $args->getEntity()->willReturn($entity);
        $entity->setPassword(ANY_ARGUMENT)->shouldNotBeCalled();

        $this->preUpdate($args);
    }
}
