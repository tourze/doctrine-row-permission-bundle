<?php

namespace Tourze\DoctrineRowPermissionBundle\Tests\Request;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\DoctrineRowPermissionBundle\Interface\PermissionConstantInterface;
use Tourze\DoctrineRowPermissionBundle\Request\GrantRowPermissionRequest;

class GrantRowPermissionRequestTest extends TestCase
{
    /**
     * 测试 getters 和 setters
     */
    public function testGettersAndSetters(): void
    {
        $request = new GrantRowPermissionRequest();
        
        $user = $this->createMock(UserInterface::class);
        $object = new \stdClass();
        $object->id = '123';
        
        // 测试 user 属性
        $this->assertSame($request, $request->setUser($user));
        $this->assertSame($user, $request->getUser());
        
        // 测试 object 属性
        $this->assertSame($request, $request->setObject($object));
        $this->assertSame($object, $request->getObject());
        
        // 测试 view 属性
        $this->assertNull($request->getView());
        $this->assertSame($request, $request->setView(true));
        $this->assertTrue($request->getView());
        
        // 测试 edit 属性
        $this->assertNull($request->getEdit());
        $this->assertSame($request, $request->setEdit(false));
        $this->assertFalse($request->getEdit());
        
        // 测试 unlink 属性
        $this->assertNull($request->getUnlink());
        $this->assertSame($request, $request->setUnlink(true));
        $this->assertTrue($request->getUnlink());
        
        // 测试 deny 属性
        $this->assertNull($request->getDeny());
        $this->assertSame($request, $request->setDeny(false));
        $this->assertFalse($request->getDeny());
        
        // 测试 remark 属性
        $this->assertNull($request->getRemark());
        $this->assertSame($request, $request->setRemark('Test remark'));
        $this->assertEquals('Test remark', $request->getRemark());
    }
    
    /**
     * 测试从数组创建请求对象的工厂方法
     */
    public function testFromArray(): void
    {
        $user = $this->createMock(UserInterface::class);
        $object = new \stdClass();
        $object->id = '123';
        
        $permissions = [
            PermissionConstantInterface::VIEW => true,
            PermissionConstantInterface::EDIT => false,
            PermissionConstantInterface::UNLINK => true,
            PermissionConstantInterface::DENY => false,
            'remark' => 'Test remark'
        ];
        
        $request = GrantRowPermissionRequest::fromArray($user, $object, $permissions);
        
        $this->assertSame($user, $request->getUser());
        $this->assertSame($object, $request->getObject());
        $this->assertTrue($request->getView());
        $this->assertFalse($request->getEdit());
        $this->assertTrue($request->getUnlink());
        $this->assertFalse($request->getDeny());
        $this->assertEquals('Test remark', $request->getRemark());
    }
    
    /**
     * 测试仅部分权限配置的情况
     */
    public function testFromArrayWithPartialPermissions(): void
    {
        $user = $this->createMock(UserInterface::class);
        $object = new \stdClass();
        $object->id = '123';
        
        $permissions = [
            PermissionConstantInterface::VIEW => true,
        ];
        
        $request = GrantRowPermissionRequest::fromArray($user, $object, $permissions);
        
        $this->assertSame($user, $request->getUser());
        $this->assertSame($object, $request->getObject());
        $this->assertTrue($request->getView());
        $this->assertNull($request->getEdit());
        $this->assertNull($request->getUnlink());
        $this->assertNull($request->getDeny());
        $this->assertNull($request->getRemark());
    }
} 