<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>MMViewer Test</title>

</head>
<body>
</body>
</html>

<script src="js/libs/ammo.js"></script>

<script type="module">

    import { MMViewer } from "/js/mmviewer/mmviewer.js";

    // Physics global
    let physicsWorld = null;
    let tmpTrans = null;        // for ammo.js transform object
    let colGroupPlane = 1, colGroupRedBall = 2, colGroupGreenBall = 4;
    let rigidBodies = [];

    // Viewer globals
    let viewer = null;

    // Ammojs Initialization
    Ammo().then(start);
    
    function start() {

        // Set up global world transform
        tmpTrans = new Ammo.btTransform();
        
        // Set up physics and graphics
        setUpPhysicsWorld();
        setUpGraphics();
        
        // Add Plane and Ball into viewer (scene)
        createBlock();
        //createBalls();
        createGreenBall();
        createRedBall();
        createJointObjects();
        
        // Animate scene with call back to update physics with delta time
        viewer.animateWithCallBack(function() {

            let deltaTime = viewer.getDeltaTime();
            updatePhysics(deltaTime);

        });

    }

    // Physics world initialization
    function setUpPhysicsWorld() {

        let collisionConfiguration = new Ammo.btDefaultCollisionConfiguration(),
            dispatcher             = new Ammo.btCollisionDispatcher(collisionConfiguration),
            overlappingPairCache   = new Ammo.btDbvtBroadphase(),
            solver                 = new Ammo.btSequentialImpulseConstraintSolver();

        physicsWorld = new Ammo.btDiscreteDynamicsWorld(dispatcher, overlappingPairCache, solver, collisionConfiguration);
        physicsWorld.setGravity(new Ammo.btVector3(0, -50, 0));

    }

    // Viewer initialization
    function setUpGraphics() {

        // MMViewer initialization
        let o = {fov: 30, near: 1, far: 1500};
        viewer = new MMViewer(o);
        
        // Start clock and set background color
        viewer.initClock();
        viewer.initStats();
        viewer.setBackgroundColor(0xbfd1e5);
        
        // Enable shadow map
        viewer.enableShadowMap();
        
        // Set Camera pos and orientation
        viewer.setCameraPos(0, 500, 1000);
        viewer.setCameraLookAt(0, 0, 0);
        
        // Adding hemisphere light to scene
        viewer.addHemiLight(0xffffff, 0xffffff, 0.1);
        viewer.setHemiLightHSL(0.6, 0.6, 0.6);
        viewer.setHemiLightGroundHSL(0.1, 1, 0.4);
        viewer.setHemiLightPosition(0, 50, 0);
        viewer.addHemiLightHelper(5);
        
        // Adding directional light to scene
        viewer.addDirectionalLight(0xffffff, 1);
        viewer.setDirectionalLightHSL(0.1, 1, 0.95);
        viewer.setDirectionalLightPosition(-1, 1.75, 1);
        viewer.setDirectionalLightScalar(100);
        viewer.setDirectionalLightShadowMap(2048, 2048);
        let d = 1000;
        viewer.setDirectionalLightFrustrum(-d, d, d, -d, 13500);
        viewer.addDirectionalLightHelper(5);
        
        // Add plane to scene
        viewer.addPlane(1000, 20);
        
        // Initialize orbit controls
        viewer.initOrbitControls(100, 1000);
    
    }

    function createBlock() {
    
        let pos = {x: 0, y: 0, z: 0};
        let scale = {x: 500, y: 2, z: 500};
        let quat = {x: 0, y: 0, z: 0, w: 1};
        let mass = 0;

        // threeJS Section
        if (viewer !== null) {

            viewer.addObject({ type: 'box', position: [pos.x, pos.y, pos.z], scale: [scale.x, scale.y, scale.z], castShadow: true, receiveShadow: true, color: 0xbfd1e5 });
        
        }

        //Ammojs Section
        let transform = new Ammo.btTransform();
        transform.setIdentity();
        transform.setOrigin(new Ammo.btVector3(pos.x, pos.y, pos.z));
        transform.setRotation(new Ammo.btQuaternion(quat.x, quat.y, quat.z, quat.w));
        
        let motionState = new Ammo.btDefaultMotionState(transform);
        let colShape = new Ammo.btBoxShape(new Ammo.btVector3(scale.x * 0.5, scale.y * 0.5, scale.z * 0.5));
        colShape.setMargin( 0.05 );

        let localInertia = new Ammo.btVector3(0, 0, 0);
        colShape.calculateLocalInertia(mass, localInertia);

        let rbInfo = new Ammo.btRigidBodyConstructionInfo(mass, motionState, colShape, localInertia);
        let body = new Ammo.btRigidBody(rbInfo);
        body.setRestitution(0.8);
        physicsWorld.addRigidBody(body, colGroupPlane, colGroupRedBall | colGroupGreenBall);

    }

    function createBalls() {

        let radius = 2;
        let quat = {x: 0, y: 0, z: 0, w: 1};
        let mass = 5;
        let colShape = new Ammo.btSphereShape(radius);
        colShape.setMargin(0.05);
        let localInertia = new Ammo.btVector3(1, 0, 0);
        colShape.calculateLocalInertia(mass, localInertia);
        
        for (let i = 0; i < 50; i++) {
            
            let pos = {x: Math.floor(Math.random() * (100 - (-100))) + (-100), y: Math.floor(Math.random() * (1000 - (500))) + (500), z: Math.floor(Math.random() * (100 - (-100))) + (-100)};
            let color = Math.random() * 0xffffff;
            createBall(pos, radius, quat, mass, color, colShape, localInertia);

        }

    }

    function createBall(pos, radius, quat, mass, color, colShape, localInertia) {
    
        let ballIndex;

        // threeJS Section
        if (viewer !== null) {

            ballIndex = viewer.addSphere(radius, color);
            viewer.setObjectPos(ballIndex, pos.x, pos.y, pos.z);
            viewer.setObjectCastShadow(ballIndex, true);
            viewer.setObjectReceiveShadow(ballIndex, true);

        }

        //Ammojs Section
        let transform = new Ammo.btTransform();
        transform.setIdentity();
        transform.setOrigin(new Ammo.btVector3(pos.x, pos.y, pos.z));
        transform.setRotation(new Ammo.btQuaternion(quat.x, quat.y, quat.z, quat.w));
        
        let motionState = new Ammo.btDefaultMotionState(transform);
        let rbInfo = new Ammo.btRigidBodyConstructionInfo(mass, motionState, colShape, localInertia);
        let body = new Ammo.btRigidBody(rbInfo);
        body.setRestitution(1);
        body.setFriction(4);
        body.setRollingFriction(10);
        // body.setDamping( 0.8, 0 );
        physicsWorld.addRigidBody(body, colGroupRedBall, colGroupPlane | colGroupRedBall);

        let ballObject = viewer.getObject(ballIndex);
        ballObject.userData.physicsBody = body;
        rigidBodies.push(ballObject);
    
    }

    function createRedBall() {
    
        let pos = {x: 0, y: 20, z: 0};
        let radius = 2;
        let quat = {x: 0, y: 0, z: 0, w: 1};
        let mass = 5;
        let ballIndex;

        // threeJS Section
        if (viewer !== null) {

            ballIndex = viewer.addSphere(radius);
            viewer.setObjectPos(ballIndex, pos.x, pos.y, pos.z);
            viewer.setObjectCastShadow(ballIndex, true);
            viewer.setObjectReceiveShadow(ballIndex, true);

        }

        //Ammojs Section
        let transform = new Ammo.btTransform();
        transform.setIdentity();
        transform.setOrigin(new Ammo.btVector3(pos.x, pos.y, pos.z));
        transform.setRotation(new Ammo.btQuaternion(quat.x, quat.y, quat.z, quat.w));
        
        let motionState = new Ammo.btDefaultMotionState(transform);
        let colShape = new Ammo.btSphereShape(radius);
        colShape.setMargin(0.05);

        let localInertia = new Ammo.btVector3(1, 0, 0);
        colShape.calculateLocalInertia(mass, localInertia);

        let rbInfo = new Ammo.btRigidBodyConstructionInfo(mass, motionState, colShape, localInertia);
        let body = new Ammo.btRigidBody(rbInfo);
        body.setRestitution(1);
        body.setFriction(4);
        body.setRollingFriction(10);
        // body.setDamping( 0.8, 0 );
        physicsWorld.addRigidBody(body, colGroupRedBall, colGroupGreenBall | colGroupPlane);

        let ballObject = viewer.getObject(ballIndex);
        ballObject.userData.physicsBody = body;
        rigidBodies.push(ballObject);
    
    }

    function createGreenBall(){
    
        let pos = {x: 1, y: 30, z: 0};
        let radius = 2;
        let quat = {x: 0, y: 0, z: 0, w: 1};
        let mass = 5;
        let ballIndex;

        //threeJS Section
        if (viewer !== null) {

            ballIndex = viewer.addSphere(radius, 0x00ff08);
            viewer.setObjectPos(ballIndex, pos.x, pos.y, pos.z);
            viewer.setObjectCastShadow(ballIndex, true);
            viewer.setObjectReceiveShadow(ballIndex, true);

        }

        //Ammojs Section
        let transform = new Ammo.btTransform();
        transform.setIdentity();
        transform.setOrigin(new Ammo.btVector3(pos.x, pos.y, pos.z));
        transform.setRotation(new Ammo.btQuaternion(quat.x, quat.y, quat.z, quat.w));
        
        let motionState = new Ammo.btDefaultMotionState(transform);
        let colShape = new Ammo.btSphereShape(radius);
        colShape.setMargin(0.05);
        let localInertia = new Ammo.btVector3(0, 0, 0);
        colShape.calculateLocalInertia(mass, localInertia);

        let rbInfo = new Ammo.btRigidBodyConstructionInfo(mass, motionState, colShape, localInertia);
        let body = new Ammo.btRigidBody(rbInfo);
        body.setRestitution(1);
        body.setFriction(4);
        body.setRollingFriction(10);
        //body.setDamping( 0.8, 0 );
        physicsWorld.addRigidBody(body, colGroupGreenBall, colGroupRedBall | colGroupPlane);
        
        let ballObject = viewer.getObject(ballIndex);
        ballObject.userData.physicsBody = body;
        rigidBodies.push(ballObject);

    }

    function createJointObjects(){
    
        let pos1 = {x: -1, y: 15, z: 0};
        let pos2 = {x: -1, y: 10, z: 0};

        let radius = 2;
        let scale = {x: 5, y: 2, z: 2};
        let quat = {x: 0, y: 0, z: 0, w: 1};
        let mass1 = 0;
        let mass2 = 1;
        let ballIndex;
        let blockIndex;

        let transform = new Ammo.btTransform();

        //Sphere Graphics
        if (viewer !== null) {

            ballIndex = viewer.addSphere(radius, 0xb846db);
            viewer.setObjectPos(ballIndex, pos1.x, pos1.y, pos1.z);
            viewer.setObjectCastShadow(ballIndex, true);
            viewer.setObjectReceiveShadow(ballIndex, true);

        }

        //Sphere Physics
        transform.setIdentity();
        transform.setOrigin(new Ammo.btVector3(pos1.x, pos1.y, pos1.z));
        transform.setRotation(new Ammo.btQuaternion(quat.x, quat.y, quat.z, quat.w));
        let motionState = new Ammo.btDefaultMotionState(transform);

        let sphereColShape = new Ammo.btSphereShape(radius);
        sphereColShape.setMargin(0.05);
        let localInertia = new Ammo.btVector3(0, 0, 0);
        sphereColShape.calculateLocalInertia(mass1, localInertia);

        let rbInfo = new Ammo.btRigidBodyConstructionInfo(mass1, motionState, sphereColShape, localInertia);
        let sphereBody = new Ammo.btRigidBody(rbInfo);
        physicsWorld.addRigidBody(sphereBody, colGroupGreenBall, colGroupRedBall);

        let ballObject = viewer.getObject(ballIndex);
        ballObject.userData.physicsBody = sphereBody;
        rigidBodies.push(ballObject);

        //Block Graphics
        if (viewer !== null) {

            blockIndex = viewer.addBox(1, 1, 1, 0xf78a1d);
            viewer.setObjectPos(blockIndex, pos2.x, pos2.y, pos2.z);
            viewer.setObjectScale(blockIndex, scale.x, scale.y, scale.z);
            viewer.setObjectCastShadow(blockIndex, true);
            viewer.setObjectReceiveShadow(blockIndex, true);

        }
        
        //Block Physics
        transform.setIdentity();
        transform.setOrigin(new Ammo.btVector3(pos2.x, pos2.y, pos2.z));
        transform.setRotation(new Ammo.btQuaternion(quat.x, quat.y, quat.z, quat.w));
        motionState = new Ammo.btDefaultMotionState(transform);

        let blockColShape = new Ammo.btBoxShape(new Ammo.btVector3(scale.x * 0.5, scale.y * 0.5, scale.z * 0.5));
        blockColShape.setMargin(0.05);
        localInertia = new Ammo.btVector3(0, 0, 0);
        blockColShape.calculateLocalInertia(mass2, localInertia);

        rbInfo = new Ammo.btRigidBodyConstructionInfo(mass2, motionState, blockColShape, localInertia);
        let blockBody = new Ammo.btRigidBody(rbInfo);
        physicsWorld.addRigidBody(blockBody, colGroupGreenBall, colGroupRedBall);
        
        let blockObject = viewer.getObject(blockIndex);
        blockObject.userData.physicsBody = blockBody;
        rigidBodies.push(blockObject);
        
        //Create Joints
        let spherePivot = new Ammo.btVector3(0, -radius, 0);
        let blockPivot = new Ammo.btVector3(-scale.x * 0.5, 1, 1);
        let p2p = new Ammo.btPoint2PointConstraint(sphereBody, blockBody, spherePivot, blockPivot);
        physicsWorld.addConstraint(p2p, false);

    }

    function updatePhysics(deltaTime) {

        // Step world
        physicsWorld.stepSimulation(deltaTime, 10);

        // Update rigid bodies
        for (let i = 0; i < rigidBodies.length; i++) {
            
            let objThree = rigidBodies[i];
            let objAmmo = objThree.userData.physicsBody;
            let ms = objAmmo.getMotionState();
                
            if (ms) {
                
                ms.getWorldTransform(tmpTrans);
                let p = tmpTrans.getOrigin();
                let q = tmpTrans.getRotation();
                objThree.position.set(p.x(), p.y(), p.z());
                objThree.quaternion.set(q.x(), q.y(), q.z(), q.w());
                
            }

        }

    }

</script>