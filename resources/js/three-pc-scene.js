import {
    ACESFilmicToneMapping,
    BoxGeometry,
    CylinderGeometry,
    DirectionalLight,
    DoubleSide,
    ExtrudeGeometry,
    Group,
    HemisphereLight,
    MathUtils,
    Mesh,
    MeshBasicMaterial,
    MeshPhysicalMaterial,
    MeshStandardMaterial,
    PMREMGenerator,
    PerspectiveCamera,
    Path,
    PointLight,
    Shape,
    Scene,
    SRGBColorSpace,
    TorusGeometry,
    WebGLRenderer,
} from 'three';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import { RoomEnvironment } from 'three/addons/environments/RoomEnvironment.js';
import { RoundedBoxGeometry } from 'three/addons/geometries/RoundedBoxGeometry.js';

export function initializePcPreview(preview, canvas, reducedMotion) {
    const scene = new Scene();
    const camera = new PerspectiveCamera(35, 1, 0.1, 100);
    camera.position.set(4.5, 2.55, 2.8);
    camera.lookAt(0, 0, 0);

    const renderer = new WebGLRenderer({
        canvas,
        alpha: true,
        antialias: true,
        powerPreference: 'low-power',
    });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 1.5));
    renderer.outputColorSpace = SRGBColorSpace;
    renderer.toneMapping = ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.1;
    const pmremGenerator = new PMREMGenerator(renderer);
    const environment = new RoomEnvironment();
    scene.environment = pmremGenerator.fromScene(environment, 0.04).texture;
    environment.dispose();
    pmremGenerator.dispose();
    const render = () => renderer.render(scene, camera);

    scene.add(new HemisphereLight(0xc8e8ff, 0x121827, 2.2));

    const keyLight = new DirectionalLight(0xffffff, 3);
    keyLight.position.set(3, 5, 4);
    scene.add(keyLight);

    const warmLight = new PointLight(0xf34303, 12, 8);
    warmLight.position.set(-2, 0.7, 2);
    scene.add(warmLight);

    const coolLight = new PointLight(0x43b9e8, 10, 7);
    coolLight.position.set(2, 1.5, -2);
    scene.add(coolLight);

    const computer = new Group();
    scene.add(computer);

    const caseMaterials = [
        new MeshPhysicalMaterial({
            color: 0x394958,
            emissive: 0x101822,
            emissiveIntensity: 0.8,
            metalness: 0.62,
            roughness: 0.27,
            clearcoat: 0.7,
            clearcoatRoughness: 0.2,
        }),
        new MeshPhysicalMaterial({
            color: 0x263341,
            emissive: 0x0a1018,
            emissiveIntensity: 0.8,
            metalness: 0.58,
            roughness: 0.32,
            clearcoat: 0.45,
            clearcoatRoughness: 0.24,
        }),
    ];
    const boardMaterial = new MeshStandardMaterial({ color: 0x294847, metalness: 0.3, roughness: 0.55 });
    const componentMaterial = new MeshStandardMaterial({ color: 0x94a9bf, metalness: 0.65, roughness: 0.28 });
    const darkMaterial = new MeshStandardMaterial({ color: 0x1b2735, metalness: 0.35, roughness: 0.48 });
    const fanBladeMaterial = new MeshStandardMaterial({ color: 0x344354, metalness: 0.35, roughness: 0.52 });
    const fanHubMaterial = new MeshStandardMaterial({ color: 0x65788b, metalness: 0.5, roughness: 0.35 });
    const glassMaterial = new MeshPhysicalMaterial({
        color: 0x8acde8,
        transparent: true,
        opacity: 0.16,
        metalness: 0.02,
        roughness: 0.08,
        clearcoat: 1,
        clearcoatRoughness: 0.08,
        side: DoubleSide,
    });
    const rgbMaterials = [
        new MeshBasicMaterial({ color: 0xf34303 }),
        new MeshBasicMaterial({ color: 0x56c9f0 }),
        new MeshBasicMaterial({ color: 0xffb51b }),
    ];

    const addBox = (size, position, material, parent = computer) => {
        const mesh = new Mesh(new BoxGeometry(...size), material);
        mesh.position.set(...position);
        parent.add(mesh);
        return mesh;
    };

    const addRoundedBox = (size, position, material, radius = 0.025, parent = computer) => {
        const mesh = new Mesh(new RoundedBoxGeometry(...size, 4, radius), material);
        mesh.position.set(...position);
        parent.add(mesh);
        return mesh;
    };

    const addCylinder = (radius, height, position, material, axis = 'z', parent = computer) => {
        const mesh = new Mesh(new CylinderGeometry(radius, radius, height, 24), material);
        mesh.position.set(...position);
        if (axis === 'z') {
            mesh.rotation.x = Math.PI / 2;
        } else if (axis === 'x') {
            mesh.rotation.z = -Math.PI / 2;
        }
        parent.add(mesh);
        return mesh;
    };

    const addRing = (radius, tube, position, material, faceSide = false, parent = computer) => {
        const mesh = new Mesh(new TorusGeometry(radius, tube, 10, 32), material);
        mesh.position.set(...position);
        if (faceSide) {
            mesh.rotation.y = Math.PI / 2;
        }
        parent.add(mesh);
        return mesh;
    };

    addRoundedBox([1.72, 0.12, 1.28], [0, -1.25, 0], caseMaterials[0], 0.045);
    addRoundedBox([1.72, 0.1, 1.28], [0, 1.25, 0], caseMaterials[0], 0.04);
    addRoundedBox([0.1, 2.35, 1.25], [-0.82, 0, 0], caseMaterials[1], 0.035);
    const frontPanelShape = new Shape();
    frontPanelShape.moveTo(-0.81, -1.175);
    frontPanelShape.lineTo(0.81, -1.175);
    frontPanelShape.lineTo(0.81, 1.175);
    frontPanelShape.lineTo(-0.81, 1.175);
    frontPanelShape.closePath();
    [-0.72, 0, 0.72].forEach((height) => {
        const fanOpening = new Path();
        fanOpening.absarc(0, height, 0.225, 0, Math.PI * 2, true);
        frontPanelShape.holes.push(fanOpening);
    });
    const frontPanel = new Mesh(
        new ExtrudeGeometry(frontPanelShape, {
            depth: 0.08,
            bevelEnabled: true,
            bevelSegments: 2,
            bevelSize: 0.012,
            bevelThickness: 0.012,
            curveSegments: 32,
        }),
        caseMaterials[0],
    );
    frontPanel.position.z = 0.57;
    computer.add(frontPanel);
    addRoundedBox([0.08, 2.28, 0.12], [0.82, 0, -0.58], caseMaterials[0], 0.02);
    addRoundedBox([0.08, 2.28, 0.12], [0.82, 0, 0.58], caseMaterials[0], 0.02);
    addRoundedBox([0.08, 0.1, 1.15], [0.82, 1.16, 0], caseMaterials[0], 0.02);
    addRoundedBox([0.08, 0.1, 1.15], [0.82, -1.16, 0], caseMaterials[0], 0.02);
    addRoundedBox([0.025, 2.12, 1.04], [0.84, 0, 0], glassMaterial, 0.012);
    [-0.55, 0.55].forEach((x) => {
        [-0.4, 0.4].forEach((z) => {
            addRoundedBox([0.3, 0.12, 0.24], [x, -1.34, z], darkMaterial, 0.04);
        });
    });
    [-0.3, -0.18, -0.06, 0.06, 0.18, 0.3].forEach((z) => {
        addBox([0.38, 0.012, 0.025], [0, 1.306, z], darkMaterial);
    });
    addBox([0.34, 0.02, 0.08], [0.5, 1.31, 0.43], darkMaterial);
    addBox([0.08, 0.02, 0.08], [0.22, 1.31, 0.43], componentMaterial);

    addRoundedBox([0.08, 1.65, 0.85], [0.38, 0.05, -0.03], boardMaterial, 0.025);
    [-0.36, -0.2, -0.04, 0.12].forEach((depth) => {
        addRoundedBox([0.11, 0.55, 0.1], [0.59, 0.74, depth], componentMaterial, 0.018);
    });
    addRoundedBox([0.24, 0.32, 0.92], [0.43, -0.62, -0.02], componentMaterial, 0.045);
    addRoundedBox([0.04, 0.27, 0.86], [0.58, -0.62, -0.02], darkMaterial, 0.018);
    [-0.27, 0, 0.27].forEach((depth) => {
        addRing(0.085, 0.012, [0.61, -0.62, depth], rgbMaterials[1], true);
        addCylinder(0.037, 0.018, [0.625, -0.62, depth], fanHubMaterial, 'x');
    });
    addRoundedBox([0.86, 0.22, 0.92], [0.22, -1.02, -0.03], darkMaterial, 0.035);

    addRing(0.22, 0.035, [0.72, 0.55, 0.03], rgbMaterials[1], true);
    addBox([0.08, 0.07, 0.88], [0.77, 1.02, -0.01], darkMaterial);

    const fanBladeShape = new Shape();
    fanBladeShape.moveTo(-0.02, 0.035);
    fanBladeShape.bezierCurveTo(-0.06, 0.055, -0.09, 0.12, -0.035, 0.17);
    fanBladeShape.bezierCurveTo(0.005, 0.145, 0.045, 0.105, 0.035, 0.065);
    fanBladeShape.bezierCurveTo(0.01, 0.05, 0, 0.04, -0.02, 0.035);
    fanBladeShape.closePath();
    const fanBladeGeometry = new ExtrudeGeometry(fanBladeShape, {
        depth: 0.012,
        bevelEnabled: true,
        bevelSegments: 1,
        bevelSize: 0.002,
        bevelThickness: 0.002,
        curveSegments: 6,
    });

    const addFrontFan = (height, lightMaterial) => {
        const fan = new Group();
        fan.position.set(0, height, 0.59);
        computer.add(fan);
        addRoundedBox([0.44, 0.44, 0.04], [0, 0, -0.015], darkMaterial, 0.016, fan);
        for (let bladeIndex = 0; bladeIndex < 7; bladeIndex += 1) {
            const angle = (bladeIndex * Math.PI * 2) / 7;
            const blade = new Mesh(fanBladeGeometry, fanBladeMaterial);
            blade.position.z = 0.02;
            blade.rotation.z = angle;
            fan.add(blade);
        }
        addCylinder(0.055, 0.03, [0, 0, 0.04], fanHubMaterial, 'z', fan);
        addRing(0.19, 0.015, [0, 0, 0.075], lightMaterial, false, fan);
    };
    [-0.72, 0, 0.72].forEach((height, index) => {
        addFrontFan(height, rgbMaterials[index]);
    });

    const setCaseFinish = (finish) => {
        const color = finish === 'white' ? 0xe7edf4 : 0x394958;
        caseMaterials[0].color.setHex(color);
        caseMaterials[1].color.setHex(finish === 'white' ? 0xabb8c7 : 0x263341);
        preview.dataset.caseColor = finish;
        preview.querySelectorAll('[data-case-choice]').forEach((button) => {
            button.setAttribute('aria-pressed', String(button.dataset.caseChoice === finish));
        });
        render();
    };

    preview.querySelectorAll('[data-case-choice]').forEach((button) => {
        button.addEventListener('click', () => setCaseFinish(button.dataset.caseChoice));
    });
    setCaseFinish(preview.dataset.caseColor || 'graphite');

    const controls = new OrbitControls(camera, canvas);
    controls.enableDamping = !reducedMotion;
    controls.dampingFactor = 0.06;
    controls.enablePan = false;
    controls.minDistance = 4.2;
    controls.maxDistance = 8.5;
    controls.minPolarAngle = MathUtils.degToRad(25);
    controls.maxPolarAngle = MathUtils.degToRad(145);
    controls.autoRotate = !reducedMotion;
    controls.autoRotateSpeed = 0.8;
    controls.update();

    canvas.addEventListener('keydown', (event) => {
        const step = 0.12;
        if (event.key === 'ArrowLeft') {
            computer.rotation.y -= step;
        } else if (event.key === 'ArrowRight') {
            computer.rotation.y += step;
        } else if (event.key === 'ArrowUp') {
            computer.rotation.x = MathUtils.clamp(computer.rotation.x - step, -0.2, 0.2);
        } else if (event.key === 'ArrowDown') {
            computer.rotation.x = MathUtils.clamp(computer.rotation.x + step, -0.2, 0.2);
        } else {
            return;
        }

        event.preventDefault();
        render();
    });

    if (reducedMotion) {
        controls.addEventListener('change', render);
    } else {
        let lastFrameTime = null;
        renderer.setAnimationLoop((time) => {
            const deltaTime = lastFrameTime === null ? 0 : Math.min((time - lastFrameTime) / 1000, 0.1);
            lastFrameTime = time;
            controls.update(deltaTime);
            render();
        });
    }

    const resize = () => {
        const bounds = preview.getBoundingClientRect();
        if (!bounds.width || !bounds.height) {
            return;
        }

        renderer.setSize(bounds.width, bounds.height, false);
        camera.aspect = bounds.width / bounds.height;
        camera.updateProjectionMatrix();
        render();
    };

    if ('ResizeObserver' in window) {
        const resizeObserver = new ResizeObserver(resize);
        resizeObserver.observe(preview);
    } else {
        window.addEventListener('resize', resize, { passive: true });
    }

    resize();
    preview.dataset.ready = 'true';
}
