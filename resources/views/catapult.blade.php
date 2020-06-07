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
    let physicsWorld = null, tmpTrans = null;
    let tmpPos = new THREE.Vector3(), tmpQuat = new THREE.Quaternion();
    const STATE = { DISABLE_DEACTIVATION : 4 };
    const FLAGS = { CF_KINEMATIC_OBJECT: 2 };
    
    // Graphics globals
    let viewer = null;                  // three.js viewer global
    // mesh ojbects
    let catapultArm = null, projectile = null, bar1 = null, bar2 = null, bar3 = null, bar4 = null;
    let drawCount = 0;
    let trajectoryLine = null;
    let readyToAnimate = false;
    let launchProjectile = false;

    // Angles (in radians)
    let currentAngle = 0;
    let maxAngle = 0;
    const angle1 = 0.3926991;   // 22.5
    const angle2 = 0.785398;    // 45
    const angle3 = 1.178097;    // 67.5
    const angle4 = 1.5708;      // 90
    const maxPoints = 3000;

    Ammo().then(start);
    
    function start() {

        // Initialize global world transform
        tmpTrans = new Ammo.btTransform();
        
        // Set up physics and graphics
        initPhysics();
        initViewer();
        
        // Add Plane and Ball into viewer (scene)
        createPlane();
        createCatapult();

        // Animate scene with call back to update physics with delta time
        viewer.animateWithCallBack(function() {

            // console.log(viewer.camera.position);

            if (readyToAnimate) {

                if (currentAngle <= maxAngle) {

                    catapultArm.rotation.x += 0.05;
                    rotateAboutPoint(projectile, catapultArm.position, new THREE.Vector3(1, 0, 0), 0.05);
                    currentAngle = catapultArm.rotation.x;
                    launchProjectile = true;

                }
                else if(launchProjectile) {

                    createDynamicProjectile();
                    createTrajectoryLine();
                    let launchVector = new THREE.Vector3(0, 1, 0);
                    launchVector.applyAxisAngle(new THREE.Vector3(1, 0, 0), angle4);
                    projectile.userData.physicsBody.setLinearVelocity(new Ammo.btVector3(0, launchVector.y * 300, launchVector.z * 300));
                    launchProjectile = false;
                    //viewer.camera.position.x -= 100;

                }
                else {

                    let deltaTime = viewer.getDeltaTime();
                    updatePhysics(deltaTime);
                    //viewer.camera.position.z = projectile.position.z;
                    //viewer.setCameraLookAt(projectile.position.x, projectile.position.y, projectile.position.z);
                    if ((drawCount) <= (maxPoints * 3))
                        updateTrajectoryLine();

                }

            }

        });

    }

    // Rotates object around pivot (point) about the specified axis 
    function rotateAboutPoint(obj, point, axis, theta){

        obj.position.sub(point); // remove the offset
        obj.position.applyAxisAngle(axis, theta); // rotate the POSITION
        obj.position.add(point); // re-add the offset
        obj.rotateOnAxis(axis, theta); // rotate the OBJECT

    }

    // Initialize physics engine
    function initPhysics() {

        let collisionConfiguration = new Ammo.btDefaultCollisionConfiguration(),
            dispatcher             = new Ammo.btCollisionDispatcher(collisionConfiguration),
            overlappingPairCache   = new Ammo.btDbvtBroadphase(),
            solver                 = new Ammo.btSequentialImpulseConstraintSolver();

        physicsWorld = new Ammo.btDiscreteDynamicsWorld(dispatcher, overlappingPairCache, solver, collisionConfiguration);
        physicsWorld.setGravity(new Ammo.btVector3(0, -100, 0));

    }

    // Initialize three js canvas viewer
    function initViewer() {
    
        // Window width and height
        let width = window.innerWidth;
        let height = window.innerHeight;

        // MMViewer initialization
        viewer = new MMViewer({far: 10000});
        
        // Start clock and set background color
        viewer.initClock();
        viewer.initStats();
        viewer.setBackgroundColor(0x87ceeb);

        // Enable shadow map
        viewer.enableShadowMap();
        
        // Adding hemisphere light to scene
        viewer.addHemiLight(0xffffff, 0xffffff, 0.1);
        viewer.setHemiLightHSL(0.6, 0.6, 0.6);
        viewer.setHemiLightGroundHSL(0.1, 1, 0.4);
        viewer.setHemiLightPosition(0, 500, 100);
        
        // Adding directional light to scene
        viewer.addDirectionalLight(0xffffff, 1);
        viewer.setDirectionalLightHSL(0.1, 1, 0.95);
        viewer.setDirectionalLightPosition(-1, 1.75, 1);
        viewer.setDirectionalLightScalar(500);
        viewer.setDirectionalLightShadowMap(2048, 2048);
        let d = 1000;
        viewer.setDirectionalLightFrustrum(-d, d, d, -d, 13500);
        
        // Initialize orbit controls
        viewer.initOrbitControls(500, 1500);
        viewer.setOrbitControlsTarget(0, 0, 0);
        viewer.setCameraPos(-518, 103, -1.2);
        viewer.updateOrbitControls();
    
    }

    // Object creation
    function createPlane() {
    
        let pos = {x: 0, y: 0, z: 2300};
        let scale = {x: 500, y: 2, z: 5000};
        let quat = {x: 0, y: 0, z: 0, w: 1};
        let mass = 0;
        let planeIndex;

        // threeJS Section
        if (viewer !== null) {

            planeIndex = viewer.addObject({ type: 'box', position: [pos.x, pos.y, pos.z], scale: [scale.x, scale.y, scale.z], color: 0xbfd1e5 });

        }

        //Ammojs Section
        let transform = new Ammo.btTransform();
        transform.setIdentity();
        transform.setOrigin(new Ammo.btVector3(pos.x, pos.y, pos.z));
        transform.setRotation(new Ammo.btQuaternion(quat.x, quat.y, quat.z, quat.w));
        
        let motionState = new Ammo.btDefaultMotionState(transform);
        let colShape = new Ammo.btBoxShape(new Ammo.btVector3(scale.x * 0.5, scale.y * 0.5, scale.z * 0.5));
        colShape.setMargin(0.05);

        let localInertia = new Ammo.btVector3(0, 0, 0);
        colShape.calculateLocalInertia(mass, localInertia);

        let rbInfo = new Ammo.btRigidBodyConstructionInfo(mass, motionState, colShape, localInertia);
        let body = new Ammo.btRigidBody(rbInfo);
        body.setRestitution(0.8);
        body.setFriction(4);
        body.setRollingFriction(10);
        physicsWorld.addRigidBody(body);

        return planeIndex;

    }

    function createCatapult() {

        let gltfPromise = viewer.loadAsyncGLTF('/models/catapult.glb');
        
        gltfPromise.then(function(gltf) {

            let material = new THREE.MeshPhongMaterial({color: 0xff0505});
            let children = gltf.scene.children;
            
            for (var i = children.length - 1; i >= 0; i--) {
                
                if (children[i].isMesh) {

                    children[i].material.dispose();
                    children[i].material = material;
                    let name = children[i].name;
                    
                    if (name === 'barWRidges002') {
                        bar1 = children[i];
                        children[i].visible = false;
                    }
                    else if (name === 'barWRidges003') {
                        bar2 = children[i];
                        children[i].visible = false;
                    }
                    else if (name === 'barWRidges004') {
                        bar3 = children[i];
                        children[i].visible = false;
                    }
                    else if (name === 'barWRidges005') {
                        bar4 = children[i];
                        children[i].visible = false;
                    }

                }

            }

            catapultArm = gltf.scene.children[7];
            projectile = gltf.scene.children[11];
            
            currentAngle = catapultArm.rotation.x;
            maxAngle = currentAngle + angle4;
            
            viewer.groupArray.push(gltf.scene);
            viewer.scene.add(gltf.scene);
            readyToAnimate = true;

        });

    }

    function createDynamicProjectile() {
        
        let mass = 1;
        let radius = 7.5;

        //Ammojs Section
        let transform = new Ammo.btTransform();
        transform.setIdentity();
        transform.setOrigin(new Ammo.btVector3(projectile.position.x, projectile.position.y, projectile.position.z));
        transform.setRotation(new Ammo.btQuaternion(projectile.quaternion.x, projectile.quaternion.y, projectile.quaternion.z, projectile.quaternion.w));
        
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
        physicsWorld.addRigidBody(body);

        // Store ammo js physics prototype
        projectile.userData.physicsBody = body;

    }

    function createTrajectoryLine() {
        
        var material = new THREE.LineBasicMaterial({ color: 0x0000ff, depthTest: false });
        
        let geometry = new THREE.BufferGeometry();
        let positions = new Float32Array(maxPoints * 3); // 3 vertices per point
        geometry.setAttribute('position', new THREE.BufferAttribute(positions, 3));

        trajectoryLine = new THREE.Line(geometry, material);
        viewer.meshArray.push(trajectoryLine);
        viewer.scene.add(trajectoryLine);
    }

    function updateTrajectoryLine() {

        var positions = trajectoryLine.geometry.attributes.position.array;
        positions[drawCount * 3] = projectile.position.x;
        positions[(drawCount * 3) + 1] = projectile.position.y;
        positions[(drawCount * 3) + 2] = projectile.position.z;
        drawCount += 1;
        trajectoryLine.geometry.setDrawRange(0, drawCount);
        trajectoryLine.geometry.attributes.position.needsUpdate = true; // required after the first render
        trajectoryLine.geometry.computeBoundingSphere();

    }

    function updatePhysics(deltaTime) {

        if (deltaTime === 0)
            return;

        // Step world
        physicsWorld.stepSimulation(deltaTime, 10);

        let objAmmo = projectile.userData.physicsBody;
        let ms = objAmmo.getMotionState();

        if (ms) {
            
            ms.getWorldTransform(tmpTrans);
            let p = tmpTrans.getOrigin();
            let q = tmpTrans.getRotation();
            projectile.position.set(p.x(), p.y(), p.z());
            projectile.quaternion.set(q.x(), q.y(), q.z(), q.w());
            
        }

    }

</script>