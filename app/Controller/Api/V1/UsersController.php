<?php

namespace App\Controller\Api\V1;

use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Phper666\JWTAuth\Middleware\JWTAuthMiddleware;
use App\Service\FriendService;
use App\Service\UserService;

class UsersController extends CController
{
    /**
     * @Inject
     * @var FriendService
     */
    protected $friendService;

    /**
     * @Inject
     * @var UserService
     */
    protected $userService;

    /**
     * @RequestMapping(path="friends", methods="get")
     */
    public function getUserFriends()
    {

    }

    /**
     * @RequestMapping(path="remove-friend", methods="get")
     */
    public function removeFriend()
    {

    }

    /**
     * @RequestMapping(path="user-groups", methods="get")
     */
    public function getUserGroups()
    {

    }

    /**
     * @RequestMapping(path="detail", methods="get")
     */
    public function getUserDetail()
    {

    }

    /**
     * @RequestMapping(path="setting", methods="get")
     */
    public function getUserSetting()
    {

    }

    /**
     * @RequestMapping(path="edit-user-detail", methods="get")
     */
    public function editUserDetail()
    {

    }

    /**
     * @RequestMapping(path="edit-avatar", methods="get")
     */
    public function editAvatar()
    {

    }

    /**
     * @RequestMapping(path="search-user", methods="get")
     */
    public function searchUserInfo()
    {

    }

    /**
     * @RequestMapping(path="edit-friend-remark", methods="get")
     */
    public function editFriendRemark()
    {

    }

    /**
     * @RequestMapping(path="send-friend-apply", methods="get")
     */
    public function sendFriendApply()
    {

    }

    /**
     * @RequestMapping(path="handle-friend-apply", methods="get")
     */
    public function handleFriendApply()
    {

    }

    /**
     * @RequestMapping(path="delete-friend-apply", methods="get")
     */
    public function deleteFriendApply()
    {

    }

    /**
     * @RequestMapping(path="friend-apply-records", methods="get")
     */
    public function getFriendApplyRecords()
    {

    }

    /**
     * @RequestMapping(path="friend-apply-num", methods="get")
     */
    public function getApplyUnreadNum()
    {

    }

    /**
     * @RequestMapping(path="change-password", methods="get")
     */
    public function editUserPassword()
    {

    }

    /**
     * @RequestMapping(path="change-mobile", methods="get")
     */
    public function editUserMobile()
    {

    }

    /**
     * @RequestMapping(path="change-email", methods="get")
     */
    public function editUserEmail()
    {

    }

    /**
     * @RequestMapping(path="send-mobile-code", methods="get")
     */
    public function sendMobileCode()
    {

    }

    /**
     * @RequestMapping(path="send-change-email-code", methods="get")
     */
    public function sendChangeEmailCode()
    {

    }
}