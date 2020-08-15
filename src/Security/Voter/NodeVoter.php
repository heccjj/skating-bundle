<?php
namespace Heccjj\SkatingBundle\Security\Voter;

use Heccjj\SkatingBundle\Entity\Node;
use Heccjj\SkatingBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class NodeVoter extends Voter
{   
    const VIEW = 'view';
    const EDIT = 'edit';
    const DELETE = 'delete';
    protected function supports(string $attribute, $subject)
    {
        if (!in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])) {
            return false;
        }

        //if (!$subject instanceof Node) {  //grant时传入node id号才可检测成功
        //    return false;
        //}

        return true;
        //return $attribute === 'CAN_EDIT_NODE' && $subject instanceof Node;
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        switch ($attribute) {
            case self::VIEW:
                return $this->canView($token, $subject, $user);
            case self::EDIT:
                return $this->canEdit($token, $subject, $user);
            case self::DELETE:
                return $this->canDelete($token, $subject, $user);
        }

        throw new \LogicException('This code should not be reached!');

        //return $subject->getOwner()->getId() === $token->getUsername() && !$subject->isLocked();
    }

    private function canView($token, $post, User $user)
    {
        return true;

         //超级用户
        foreach ($token->getRoleNames() as $role) {
            if (in_array($role, ['ROLE_MODERATOR', 'ROLE_ADMIN'])) {
                return true;
            }
         } 

        //可编辑内容
        if ($this->canEdit($token, $post, $user)) {
           return true;
        }

    }

    private function canEdit($token, $post, User $user)
    {
        //超级用户
        foreach ($token->getRoleNames() as $role) {
            if (in_array($role, ['ROLE_MODERATOR', 'ROLE_ADMIN'])) {
                return true;
            }
         }

        //自己的文件
        return $user === $post->getAuthor();
    }

    private function canDelete($token, $post, User $user)
    {
        //超级用户
        foreach ($token->getRoleNames() as $role) {
            if (in_array($role, ['ROLE_MODERATOR', 'ROLE_ADMIN'])) {
                return true;
            }
         } 

        //自己的文件
        return $user === $post->getAuthor();
    }
}