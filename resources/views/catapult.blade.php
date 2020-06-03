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

    import { MMViewer, THREE } from "/js/mmviewer/mmviewer.js";

    // Physics globals
    let physicsWorld = null;            // ammo.js physics world
    let tmpTrans = null;                // global world transform
    let rigidBodies = [];               // ammo.js rigid bodies for collision
    let colGroup1 = 1, colGroup2 = 2;   // colision masks
    // Graphics globals
    let viewer = null;                  // three.js viewer global
    let readyToAnimate = false;
    // Angles
    let currentAngle = 0;
    let maxAngle = 0;
    const angle1 = 0.3926991;
    const angle2 = 0.785398;
    const angle3 = 1.178097;
    const angle4 = 1.5708;

    Ammo().then(start);
    
    function start() {

        // Initialize global world transform
        tmpTrans = new Ammo.btTransform();
        
        // Set up physics and graphics
        initPhysics();
        initViewer();
        
        // Add Plane and Ball into viewer (scene)
        createPlane();
        createBall();
        createCatapult();

        // viewer.animate();

        // Animate scene with call back to update physics with delta time
        viewer.animateWithCallBack(function() {

            if (readyToAnimate) {

                if (currentAngle <= maxAngle) {

                    //let group = new THREE.Group();
                    //console.log(viewer.groupArray[0].children[7]);
                    // group.add(viewer.groupArray[0].children[7]);
                    //group.add(viewer.groupArray[0].children[11]);
                    //group.rotation.x += 0.05;

                    // viewer.groupArray[0].children[7].rotation.x += 0.05;
                    // currentAngle = viewer.groupArray[0].children[7].rotation.x;

                    // viewer.groupArray[0].children[11].position.applyAxisAngle(new THREE.Vector3(1, 0, 0), 0.05);
                    // viewer.groupArray[0].children[11].position.z = z;
                    // console.log(viewer.groupArray[0].children[11].position);
                    // Rotation matrix;
                    // viewer.groupArray[0].children[11].position.z += 2;
                    // viewer.groupArray[0].children[11].position.y += 2;
                }

            }
            let deltaTime = viewer.getDeltaTime();
            updatePhysics(deltaTime);
            // console.log(viewer.camera.position);

        });

    }

    // Initialize physics engine
    function initPhysics() {

        let collisionConfiguration = new Ammo.btDefaultCollisionConfiguration(),
            dispatcher             = new Ammo.btCollisionDispatcher(collisionConfiguration),
            overlappingPairCache   = new Ammo.btDbvtBroadphase(),
            solver                 = new Ammo.btSequentialImpulseConstraintSolver();

        physicsWorld = new Ammo.btDiscreteDynamicsWorld(dispatcher, overlappingPairCache, solver, collisionConfiguration);
        physicsWorld.setGravity(new Ammo.btVector3(0, -50, 0));

    }

    // Initialize three js canvas viewer
    function initViewer() {
    
        // Window width and height
        let width = window.innerWidth;
        let height = window.innerHeight;

        // MMViewer initialization
        viewer = new MMViewer({far: 2500});
        
        // Start clock and set background color
        viewer.initClock();
        viewer.initStats();
        viewer.setBackgroundColor(0x87ceeb);

        // Enable shadow map
        viewer.enableShadowMap();
        
        // Set Camera pos and orientation
        //viewer.setCameraPos(803, 121, -459);
        //viewer.setCameraLookAt(0, 2, -450);

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
        viewer.initOrbitControls(500, 1500);
        viewer.setOrbitControlsTarget(0, 2, -450);
        viewer.setCameraPos(-493, 86, -434);
        viewer.updateOrbitControls();
    
    }

    // Object creation
    function createPlane() {
    
        let pos = {x: 0, y: 0, z: 0};
        let scale = {x: 500, y: 2, z: 1000};
        let quat = {x: 0, y: 0, z: 0, w: 1};
        let mass = 0;
        let planeIndex;

        // threeJS Section
        if (viewer !== null) {

            planeIndex = viewer.addObject({ type: 'box', position: [pos.x, pos.y, pos.z], scale: [scale.x, scale.y, scale.z], castShadow: true, receiveShadow: true, color: 0xbfd1e5 });
        
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
        physicsWorld.addRigidBody(body, colGroup2, colGroup1);

        return planeIndex;

    }

    function createBall() {
    
        let pos = {x: 0, y: 2, z: -500};
        let quat = {x: 0, y: 0, z: 0, w: 1};
        let radius = 2;
        let mass = 1;
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
        body.setRestitution(0.8);
        body.setFriction(4);
        body.setRollingFriction(10);
        physicsWorld.addRigidBody(body, colGroup1, colGroup2);

        // Grab Mesh object and store ammo js prototype in it
        let ballObject = viewer.getObject(ballIndex);
        ballObject.userData.physicsBody = body;
        ballObject.userData.physicsBody.setLinearVelocity(new Ammo.btVector3(0, 80, 150));
        rigidBodies.push(ballObject);

        return ballIndex;
    
    }

    function createCatapult() {

        let gltfPromise = viewer.loadAsyncGLTF('/models/catapult.glb');
        gltfPromise.then(function(gltf) {

            for (var i = gltf.scene.children.length - 1; i >= 0; i--) {
                
                let children = gltf.scene.children;
                //let test = new THREE.Group();
                for (var i = children.length - 1; i >= 0; i--) {
                    
                    if (children[i].isMesh) {

                        if (children[i].name === 'barWRidges002')
                            children[i].visible = false;
                        if (children[i].name === 'barWRidges003')
                            children[i].visible = false;
                        if (children[i].name === 'barWRidges004')
                            children[i].visible = false;
                        if (children[i].name === 'barWRidges005')
                            children[i].visible = false;


                        children[i].material.dispose();
                        children[i].material = new THREE.MeshPhongMaterial({color: 0xff0505});
                        //test.add(children[i]);
                    }

                }

                gltf.scene.position.set(0, 0, -450);
                gltf.scene.castShadow = true;
                gltf.scene.receiveShadow = true;
                viewer.groupArray.push(gltf.scene);
                viewer.scene.add(gltf.scene);
                readyToAnimate = true;
                currentAngle = viewer.groupArray[0].children[7].rotation.x;
                maxAngle = currentAngle + angle2;

            }

            // console.log(viewer.groupArray[0]);
        });


    }

    function updatePhysics(deltaTime) {

        // Step world
        physicsWorld.stepSimulation(deltaTime, 10);

        // Update rigid bodies
        for (let i = 0; i < rigidBodies.length; i++) {
            
            let objThree = rigidBodies[i];
            let objAmmo = objThree.userData.physicsBody;
            let ms = objAmmo.getMotionState();
            // objAmmo.applyForce(new Ammo.btVector3(0, 100, 20));
                
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