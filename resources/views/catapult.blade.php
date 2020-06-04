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
    let physicsWorld = null, tmpTrans = null, ammoTmpPos = null, ammoTmpQuat = null;
    let rigidBodies = [];               // ammo.js rigid bodies for collision
    let colGroup1 = 1, colGroup2 = 2, colGroup3 = 4;   // colision masks
    let tmpPos = new THREE.Vector3(), tmpQuat = new THREE.Quaternion();
    const STATE = { DISABLE_DEACTIVATION : 4 };
    const FLAGS = { CF_KINEMATIC_OBJECT: 2 };
    
    // Graphics globals
    let viewer = null;                  // three.js viewer global
    let readyToAnimate = false;
    
    // Angles (in radians)
    let currentAngle = 0;
    let maxAngle = 0;
    const angle1 = 0.3926991;   // 22.5
    const angle2 = 0.785398;    // 45
    const angle3 = 1.178097;    // 67.5
    const angle4 = 1.5708;      // 90

    Ammo().then(start);
    
    function start() {

        // Initialize global world transform
        tmpTrans = new Ammo.btTransform();
        ammoTmpPos = new Ammo.btVector3();
        ammoTmpQuat = new Ammo.btQuaternion();
        
        // Set up physics and graphics
        initPhysics();
        initViewer();
        
        // Add Plane and Ball into viewer (scene)
        createPlane();
        // createBall();
        createCatapult();

        // viewer.animate();

        // Animate scene with call back to update physics with delta time
        viewer.animateWithCallBack(function() {

            if (readyToAnimate) {

                // let catapultArm = viewer.groupArray[0].children[7];

                // if (currentAngle <= maxAngle) {

                //     catapultArm.rotation.x += 0.05;
                //     currentAngle = catapultArm.rotation.x;

                // }

                let deltaTime = viewer.getDeltaTime();
                updatePhysics(deltaTime);

            }

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

    function createCatapult() {

        let gltfPromise = viewer.loadAsyncGLTF('/models/catapult.glb');
        
        gltfPromise.then(function(gltf) {

            let material = new THREE.MeshPhongMaterial({color: 0xff0505});
            gltf.scene.position.set(0, 0, -450);
            gltf.scene.castShadow = true;
            gltf.scene.receiveShadow = true;
            // console.log(gltf.scene);
            
            let children = gltf.scene.children;
            for (var i = children.length - 1; i >= 0; i--) {
                
                if (children[i].isMesh) {

                    children[i].material.dispose();
                    children[i].material = material;
                    
                    if (children[i].name === 'barWRidges002')
                        children[i].visible = false;
                    if (children[i].name === 'barWRidges003')
                        children[i].visible = false;
                    if (children[i].name === 'barWRidges004')
                        children[i].visible = false;
                    if (children[i].name === 'barWRidges005')
                        children[i].visible = false;

                    if (children[i].name === 'projectile') {

                        // Being part of the gltf scene group messes up the collision detection for
                        // some reason. Not adding child to another group removes it from current group
                        createDynamicProjectile(children[i]);

                    }
                    else if (children[i].name === 'catArmXL001') {

                        // Being part of the gltf scene group messes up the collision detection for
                        // some reason.
                        createKinematicCatapult(children[i]);

                    }

                }

            }

            // createDynamicProjectile(children[11]);
            // gltf.scene.remove(children[11]);
            // createKinematicCatapult(children[7]);
            // gltf.scene.remove(children[7]);

            viewer.groupArray.push(gltf.scene);
            viewer.scene.add(gltf.scene);
            console.log(gltf.scene);
            
            readyToAnimate = true;
            // currentAngle = viewer.groupArray[0].children[7].rotation.x;
            // maxAngle = currentAngle + angle2;
            // console.log(viewer.groupArray[0]);

        });


    }

    function createDynamicProjectile(threeMesh) {
        
        let mass = 1;
        let radius = 6;

        //threeJS Section
        threeMesh.castShadow = true;
        threeMesh.receiveShadow = true;
        threeMesh.position.z += -450;

        if (viewer !== null) {
            
            viewer.meshArray.push(threeMesh);
            viewer.scene.add(threeMesh);
       
        }

        //Ammojs Section
        let transform = new Ammo.btTransform();
        transform.setIdentity();
        transform.setOrigin(new Ammo.btVector3(threeMesh.position.x, threeMesh.position.y, threeMesh.position.z));
        transform.setRotation(new Ammo.btQuaternion(threeMesh.quaternion.x, threeMesh.quaternion.y, threeMesh.quaternion.z, threeMesh.quaternion.w));
        
        let motionState = new Ammo.btDefaultMotionState(transform);
        let colShape = new Ammo.btSphereShape(radius);
        colShape.setMargin(0.05);

        let localInertia = new Ammo.btVector3(0, 0, 0);
        colShape.calculateLocalInertia(mass, localInertia);

        let rbInfo = new Ammo.btRigidBodyConstructionInfo(mass, motionState, colShape, localInertia);
        let body = new Ammo.btRigidBody(rbInfo);
        body.setRestitution(0.8);
        body.setFriction(4);
        body.setRollingFriction(10);
        physicsWorld.addRigidBody(body, colGroup1, colGroup2);

        // Store ammo js physics prototype
        threeMesh.userData.physicsBody = body;
        // threeMesh.userData.physicsBody.setLinearVelocity(new Ammo.btVector3(0, 80, 150));
        rigidBodies.push(threeMesh);

    }

    function createKinematicCatapult(threeMesh) {
        
        let mass = 1;
        let triangles = [];
        let triangleMesh = new Ammo.btTriangleMesh;
        // console.log(threeMesh.geometry);
        // too lazy to implement my own algorithm to get faces. would be more efficient though.
        let geometry = new THREE.Geometry().fromBufferGeometry(threeMesh.geometry);
        let vertices = geometry.vertices;
        let vec1 = new Ammo.btVector3(0,0,0);
        let vec2 = new Ammo.btVector3(0,0,0);
        let vec3 = new Ammo.btVector3(0,0,0);
        // console.log(geometry);

        //threeJS Section
        threeMesh.castShadow = true;
        threeMesh.receiveShadow = true;
        threeMesh.position.z += -450;

        if (viewer !== null) {
            
            viewer.meshArray.push(threeMesh);
            viewer.scene.add(threeMesh);
       
        }

        //Ammojs Section
        for (let i = 0; i < geometry.faces.length; i++) {
            
            let face = geometry.faces[i];

            if ( face instanceof THREE.Face3) {

                triangles.push([
                    { x: vertices[face.a].x, y: vertices[face.a].y, z: vertices[face.a].z },
                    { x: vertices[face.b].x, y: vertices[face.b].y, z: vertices[face.b].z },
                    { x: vertices[face.c].x, y: vertices[face.c].y, z: vertices[face.c].z }
                ]);

            } else if ( face instanceof THREE.Face4 ) {

                triangles.push([
                    { x: vertices[face.a].x, y: vertices[face.a].y, z: vertices[face.a].z },
                    { x: vertices[face.b].x, y: vertices[face.b].y, z: vertices[face.b].z },
                    { x: vertices[face.d].x, y: vertices[face.d].y, z: vertices[face.d].z }
                ]);
                triangles.push([
                    { x: vertices[face.b].x, y: vertices[face.b].y, z: vertices[face.b].z },
                    { x: vertices[face.c].x, y: vertices[face.c].y, z: vertices[face.c].z },
                    { x: vertices[face.d].x, y: vertices[face.d].y, z: vertices[face.d].z }
                ]);

            }
        }

        for (let i = 0; i < triangles.length; i++) {
            
            let triangle = triangles[i];

            vec1.setX(triangle[0].x);
            vec1.setY(triangle[0].y);
            vec1.setZ(triangle[0].z);

            vec2.setX(triangle[1].x);
            vec2.setY(triangle[1].y);
            vec2.setZ(triangle[1].z);

            vec3.setX(triangle[2].x);
            vec3.setY(triangle[2].y);
            vec3.setZ(triangle[2].z);

            triangleMesh.addTriangle(vec1, vec2, vec3, true);

        }

        let colShape = new Ammo.btBvhTriangleMeshShape(triangleMesh, true, false);
        // colShape.setMargin(0.05);

        let transform = new Ammo.btTransform();
        transform.setIdentity();
        transform.setOrigin(new Ammo.btVector3(threeMesh.position.x, threeMesh.position.y, threeMesh.position.z));
        transform.setRotation(new Ammo.btQuaternion(threeMesh.quaternion.x, threeMesh.quaternion.y, threeMesh.quaternion.z, threeMesh.quaternion.w));
        let motionState = new Ammo.btDefaultMotionState(transform);


        let localInertia = new Ammo.btVector3(0, 0, 0);
        colShape.calculateLocalInertia(mass, localInertia);

        let rbInfo = new Ammo.btRigidBodyConstructionInfo(mass, motionState, colShape, localInertia);
        let body = new Ammo.btRigidBody(rbInfo);

        body.setActivationState(STATE.DISABLE_DEACTIVATION);
        body.setCollisionFlags(FLAGS.CF_KINEMATIC_OBJECT);
        physicsWorld.addRigidBody(body);
        // physicsWorld.addRigidBody(body, colGroup1, colGroup2);

        // physicsWorld.addRigidBody(body, colGroup1, colGroup2);

        
        // Store ammo js physics prototype
        threeMesh.userData.physicsBody = body;
        threeMesh.userData.physicsBody.setLinearVelocity(new Ammo.btVector3(0, 80, 150));
        rigidBodies.push(threeMesh);
        // body.setGravity(new Ammo.btVector3(0, 0, 0));

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