/**
 * IDTC DRONE — Main Controller & State Management Engine
 * Official Content integrated from IDTC Drone (PT Jogja Creative Production)
 */

// 1. Projects State Data (Real IDTC Drone Portfolio Projects)
const projectsData = [
    {
        id: 1,
        number: "01",
        category: "GOVERNMENT & AGENCY",
        title: "DISNAKER SLEMAN TRAINING",
        subtitle: "Pelatihan Drone Dinas Tenaga Kerja Kab. Sleman",
        description: "Pelatihan operasional drone dan keselamatan penerbangan bagi aparatur Dinas Tenaga Kerja Kabupaten Sleman untuk efisiensi pengawasan wilayah.",
        image: "https://images.unsplash.com/photo-1508614589041-895b88991e3e?q=80&w=1400&auto=format&fit=crop",
        fullCaseStudy: {
            clientIndustry: "Pemerintahan Daerah & Instansi Publik",
            objective: "Meningkatkan kapasitas teknis dan pengawasan lapangan staf Dinas Tenaga Kerja Kabupaten Sleman menggunakan teknologi drone.",
            technology: "Enterprise Quadcopter, High-Resolution Optical Camera, Autonomous Mission Planner",
            workflow: "Orientasi Regulasi ➔ Ground School Safety ➔ Praktik Terbang Presisi ➔ Simulasi Pengawasan Field Area ➔ Evaluasi",
            results: "Sertifikasi mahir bagi seluruh staf instansi dengan 100% kelulusan kualifikasi operasional standar."
        },
        metrics: [
            { label: "INSTANSI", value: "Disnaker Sleman" },
            { label: "LAYANAN", value: "In-House Training" },
            { label: "FOKUS", value: "Safety & Operasional" },
            { label: "STATUS", value: "Terverifikasi Mahir" }
        ]
    },
    {
        id: 2,
        number: "02",
        category: "DISASTER RELIEF",
        title: "TANGGAP BENCANA",
        subtitle: "Pelatihan Drone Penanggulangan Bencana",
        description: "Pelatihan navigasi dan pemetaan udara fast-response bagi tim relawan serta personil penanggulangan bencana alam.",
        image: "https://images.unsplash.com/photo-1527977966376-1c8408f9f108?q=80&w=1400&auto=format&fit=crop",
        fullCaseStudy: {
            clientIndustry: "Tim Relawan & Penanggulangan Bencana",
            objective: "Memberikan keahlian pemetaan cepat (rapid aerial mapping) dan pencarian visual pada lokasi terdampak bencana.",
            technology: "Thermal Sensor Drone, Long-Range Radios, Emergency Power Stations",
            workflow: "Pengenalan Medan Bencana ➔ Jalur Terbang Darurat ➔ Pemetaan Orthomosaic Cepat ➔ Analisis Titik Kritis",
            results: "Penyediaan peta situasi darurat dalam waktu di bawah 30 menit dari waktu peluncuran wahana."
        },
        metrics: [
            { label: "SEKTOR", value: "Disaster Relief" },
            { label: "LAYANAN", value: "Rapid Mapping SAR" },
            { label: "TEKNOLOGI", value: "Thermal & Long-Range" },
            { label: "WAKTU RESPON", value: "< 30 Menit" }
        ]
    },
    {
        id: 3,
        number: "03",
        category: "GEOSPATIAL",
        title: "DRONE MAPPING",
        subtitle: "Pelatihan Fotogrametri & Pemetaan Spasial",
        description: "Pelatihan pembuatan peta ortomosaik 2D/3D, kontur ketinggian, dan pengolahan data GIS untuk tata ruang serta konstruksi.",
        image: "https://images.unsplash.com/photo-1579829366248-204fe8413f31?q=80&w=1400&auto=format&fit=crop",
        fullCaseStudy: {
            clientIndustry: "Masyarakat Umum & Komunitas Surveyor",
            objective: "Penguasaan teknik fotogrametri udara presisi tinggi dari pemasangan GCP hingga pemrosesan model 3D.",
            technology: "RTK/PPK Quadcopter, Ground Control Points (GCP), Agisoft Metashape, GIS Platform",
            workflow: "Penentuan GCP ➔ Grid Flight Mission ➔ Image Matching ➔ Ortomosaik 2D ➔ Dem/Contour Generation",
            results: "Akurasi pemetaan sub-sentimeter siap pakai untuk kebutuhan tata ruang dan pemetaan lahan."
        },
        metrics: [
            { label: "PESERTA", value: "Masyarakat & Komunitas" },
            { label: "LAYANAN", value: "2D/3D Mapping" },
            { label: "AKURASI", value: "< 2 cm Margin Error" },
            { label: "OUTPUT", value: "Ortomosaik & Kontur" }
        ]
    },
    {
        id: 4,
        number: "04",
        category: "SMART AGRICULTURE",
        title: "DRONE PERTANIAN",
        subtitle: "Pelatihan Precision Agriculture",
        description: "Pelatihan penerbangan drone multispektral untuk pemantauan vegetasi lahan dan penyemprotan pupuk presisi berbasis data.",
        image: "https://images.unsplash.com/photo-1473968512647-3e447244af8f?q=80&w=1400&auto=format&fit=crop",
        fullCaseStudy: {
            clientIndustry: "Pertanian & Perkebunan Modern",
            objective: "Efisiensi perawatan tanaman melalui analisis indeks vegetasi (NDVI) dan spraying otomatis.",
            technology: "Agras Spraying Drone, Multispectral Camera, Precision Tank Nozzles",
            workflow: "Multispectral Scanning ➔ NDVI Index Mapping ➔ Prescription Map ➔ Automated Spraying Flight",
            results: "Hemat penggunaan pupuk cair hingga 30% dan peningkatan kecepatan cakupan lahan 5x lipat."
        },
        metrics: [
            { label: "SEKTOR", value: "Agri-Tech" },
            { label: "LAYANAN", value: "NDVI & Spraying" },
            { label: "EFISIENSI", value: "30% Hemat Input" },
            { label: "CAKUPAN", value: "5x Lebih Cepat" }
        ]
    },
    {
        id: 5,
        number: "05",
        category: "ENTERPRISE FLEET",
        title: "MULTI-DRONE TRAINING",
        subtitle: "Pelatihan Berbagai Jenis Wahana Drone",
        description: "Pelatihan praktis pengoperasian berbagai tipe drone FPV, quadcopter, hingga fixed-wing industri dengan standar keselamatan tinggi.",
        image: "https://images.unsplash.com/photo-1581092160607-ee22621dd758?q=80&w=1400&auto=format&fit=crop",
        fullCaseStudy: {
            clientIndustry: "Komunitas Mahasiswa & Profesional",
            objective: "Menyesuaikan materi dan wahana dengan kemampuan awal peserta serta kebutuhan organisasi.",
            technology: "Multi-Rotor Fleet, FPV Cinematic Units, Fixed-Wing Mapping Drones",
            workflow: "Leveling Initial Ability ➔ Tailored Curriculum ➔ Multi-Fleet Field Practice ➔ Advanced Maneuvers",
            results: "Peningkatan fleksibilitas pilot dalam mengendalikan berbagai tipe drone industri secara aman."
        },
        metrics: [
            { label: "VARIASI", value: "Multi-Fleet UAV" },
            { label: "FLEXIBILITY", value: "Materi Custom" },
            { label: "LEVEL", value: "Dasar hingga Mahir" },
            { label: "STANDAR", value: "Safety First SOP" }
        ]
    }
];

// Training Programs Data (Tabbed Selector Format - Official IDTC Drone Modules)
const trainingProgramsData = [
    {
        id: "01",
        title: "Basic Flight & Safety Training",
        subtitle: "Keterampilan Terbang Dasar & SOP Keselamatan",
        description: "Pelatihan tingkat dasar yang berfokus pada penguasaan kontrol penerbangan manual, pemahaman regulasi penerbangan, serta standar keselamatan penerbangan (SOP).",
        duration: "Modul Disesuaikan",
        target: "Pemula, Hobi, & Staff Operasional Instansi",
        features: [
            "Keterampilan terbang presisi dari tingkat dasar hingga mahir",
            "Fokus pada teknik penerbangan yang aman",
            "Pemahaman prosedur operasional sesuai standar penerbangan",
            "Praktik langsung di lapangan dengan panduan instruktur berpengalaman"
        ]
    },
    {
        id: "02",
        title: "Pengoperasian Kamera & Cinematic Footage",
        subtitle: "Dokumentasi Udara & Visual Stabil",
        description: "Modul khusus yang dirancang untuk membantu peserta menghasilkan footage sinematik yang stabil, menguasai gerakan kamera udara, serta profil warna profesional.",
        duration: "Modul Disesuaikan",
        target: "Fotografer, Videografer, & Dokumentasi Profesional",
        features: [
            "Teknik pengoperasian kamera udara resolusi tinggi",
            "Membantu peserta menghasilkan footage yang stabil dan halus",
            "Penguasaan sudut pengambilan gambar & pergerakan smooth orbit",
            "Praktik pengambilan gambar di berbagai kondisi pencahayaan"
        ]
    },
    {
        id: "03",
        title: "Pelatihan Drone Mapping & Fotogrametri",
        subtitle: "Pemetaan Spasial 2D/3D & Data Kontur",
        description: "Pelatihan pemetaan spasial menggunakan drone untuk pembuatan peta ortomosaik 2D, kontur ketinggian, dan model 3D yang akurat untuk lahan & konstruksi.",
        duration: "Modul Disesuaikan",
        target: "Surveyor, Komunitas Mahasiswa, & Profesional GIS",
        features: [
            "Pemasangan dan pengukuran titik kontrol lapangan (GCP)",
            "Perencanaan misi terbang otonom berbasis grid survey",
            "Pengolahan data hasil foto udara menjadi peta Ortomosaik 2D & 3D Mesh",
            "Export data ke software GIS dan CAD"
        ]
    },
    {
        id: "04",
        title: "Pelatihan Drone Pertanian Presisi",
        subtitle: "Smart Farming & Crop Monitoring",
        description: "Penguasaan drone multispektral untuk analisis kesehatan tanaman (NDVI) serta teknik penyemprotan otomatis di area lahan pertanian.",
        duration: "Modul Disesuaikan",
        target: "Praktisi Pertanian, Perkebunan, & Peneliti",
        features: [
            "Pengoperasian kamera multispektral pemantau vegetasi",
            "Pemetaan indeks kesehatan tanaman (NDVI Analysis)",
            "Kalibrasi dan penerbangan penyemprotan pupuk/pestisida otomatis",
            "Efisiensi penggunaan bahan dan penghematan waktu kerja"
        ]
    },
    {
        id: "05",
        title: "Pelatihan Khusus Instansi & Tanggap Bencana",
        subtitle: "In-House Training & Custom Programs",
        description: "Pelatihan khusus yang dirancang sesuai kebutuhan organisasi (seperti Disnaker Sleman dan Tim Tanggap Bencana) dengan jadwal dan materi yang disesuaikan.",
        duration: "Jadwal & Durasi Custom",
        target: "Instansi Pemerintah, BUMN, & Tim Relawan Bencana",
        features: [
            "Materi dan durasi pelatihan menyesuaikan kebutuhan organisasi",
            "Penerbangan pemetaan cepat area terdampak bencana (Rapid SAR Mapping)",
            "Simulasi pengawasan wilayah dan dokumentasi instansi",
            "Sertifikat keahlian resmi dari Indonesia Drone Training Center"
        ]
    }
];

document.addEventListener('DOMContentLoaded', () => {

    // ==============================================================================
    // 1. SELECTED PROJECTS SHOWCASE ENGINE
    // ==============================================================================
    let activeProjectIndex = 0;
    let autoplayInterval = null;
    let isUserInteracting = false;
    let autoplayDelayTimeout = null;

    const cardsContainer = document.getElementById('projects-cards-container');
    const infoBox = document.getElementById('info-panel-box');
    const infoCategory = document.getElementById('info-panel-category');
    const infoTitle = document.getElementById('info-panel-title');
    const infoSubtitle = document.getElementById('info-panel-subtitle');
    const infoDesc = document.getElementById('info-panel-desc');
    const infoStatsGrid = document.getElementById('info-panel-stats-grid');
    const infoCtaBtn = document.getElementById('info-panel-cta');

    const renderCardsDOM = () => {
        if (!cardsContainer) return;
        cardsContainer.innerHTML = '';

        projectsData.forEach((project, idx) => {
            const card = document.createElement('div');
            card.className = `project-card-item ${idx === activeProjectIndex ? 'active' : ''}`;
            card.dataset.index = idx;

            card.innerHTML = `
                <img src="${project.image}" alt="${project.title}" class="card-bg-image">
                <div class="card-overlay"></div>
                <div class="card-content">
                    <div class="flex justify-between items-start">
                        <span class="project-number-badge">${project.number}</span>
                        <span class="text-[10px] font-extrabold uppercase tracking-widest text-primary-orange bg-black/70 px-3 py-1 rounded border border-white/10">${project.category}</span>
                    </div>
                    <div class="project-collapsed-title">${project.title}</div>
                    <div class="project-expanded-info">
                        <span class="text-xs font-semibold text-primary-orange block mb-1 uppercase tracking-wider">${project.subtitle}</span>
                        <h3 class="text-2xl md:text-3xl font-extrabold text-white mb-2 leading-tight uppercase">${project.title}</h3>
                        <p class="text-xs text-slate-300 line-clamp-2 max-w-lg mb-4">${project.description}</p>
                        <button onclick="openCaseStudyModal(${idx})" class="btn-primary py-2.5 px-5 text-xs">
                            <span>View Case Study</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                        </button>
                    </div>
                </div>
            `;

            card.addEventListener('mouseenter', () => {
                stopAutoplay();
                selectProject(idx);
            });

            card.addEventListener('click', () => {
                isUserInteracting = true;
                stopAutoplay();
                selectProject(idx);
            });

            cardsContainer.appendChild(card);
        });

        cardsContainer.addEventListener('mouseleave', () => {
            clearTimeout(autoplayDelayTimeout);
            autoplayDelayTimeout = setTimeout(() => {
                isUserInteracting = false;
                startAutoplay();
            }, 3000);
        });
    };

    const selectProject = (index) => {
        activeProjectIndex = index;
        const current = projectsData[activeProjectIndex];

        const cardElements = document.querySelectorAll('.project-card-item');
        cardElements.forEach((el, idx) => {
            if (idx === activeProjectIndex) {
                el.classList.add('active');
            } else {
                el.classList.remove('active');
            }
        });

        if (infoBox) {
            infoBox.classList.add('panel-updating');
            setTimeout(() => {
                if (infoCategory) infoCategory.textContent = current.category;
                if (infoTitle) infoTitle.textContent = current.title;
                if (infoSubtitle) infoSubtitle.textContent = current.subtitle;
                if (infoDesc) infoDesc.textContent = current.description;
                if (infoCtaBtn) infoCtaBtn.setAttribute('onclick', `openCaseStudyModal(${activeProjectIndex})`);

                if (infoStatsGrid) {
                    infoStatsGrid.innerHTML = '';
                    current.metrics.forEach(st => {
                        const statItem = document.createElement('div');
                        statItem.className = 'stat-grid-item';
                        statItem.innerHTML = `
                            <span class="text-[10px] font-extrabold uppercase tracking-widest text-slate-400 block mb-1">${st.label}</span>
                            <span class="text-sm font-bold text-white leading-tight block">${st.value}</span>
                        `;
                        infoStatsGrid.appendChild(statItem);
                    });
                }
                infoBox.classList.remove('panel-updating');
            }, 150);
        }
    };

    const startAutoplay = () => {
        if (autoplayInterval) clearInterval(autoplayInterval);
        autoplayInterval = setInterval(() => {
            if (!isUserInteracting) {
                const nextIndex = (activeProjectIndex + 1) % projectsData.length;
                selectProject(nextIndex);
            }
        }, 5000);
    };

    const stopAutoplay = () => {
        if (autoplayInterval) clearInterval(autoplayInterval);
    };

    // Mobile Touch Gesture Support
    let touchStartX = 0;
    let touchEndX = 0;
    if (cardsContainer) {
        cardsContainer.addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
            stopAutoplay();
        }, { passive: true });

        cardsContainer.addEventListener('touchend', (e) => {
            touchEndX = e.changedTouches[0].screenX;
            if (touchStartX - touchEndX > 50) {
                selectProject((activeProjectIndex + 1) % projectsData.length);
            } else if (touchEndX - touchStartX > 50) {
                selectProject((activeProjectIndex - 1 + projectsData.length) % projectsData.length);
            }
            startAutoplay();
        }, { passive: true });
    }

    renderCardsDOM();
    selectProject(0);
    startAutoplay();


    // ==============================================================================
    // 2. CASE STUDY MODAL (Direct Viewing without Registration)
    // ==============================================================================
    const caseStudyModal = document.getElementById('case-study-modal');
    const csCategory = document.getElementById('cs-modal-category');
    const csTitle = document.getElementById('cs-modal-title');
    const csSubtitle = document.getElementById('cs-modal-subtitle');
    const csImage = document.getElementById('cs-modal-image');
    const csDesc = document.getElementById('cs-modal-desc');
    const csObjective = document.getElementById('cs-modal-objective');
    const csTech = document.getElementById('cs-modal-tech');
    const csWorkflow = document.getElementById('cs-modal-workflow');
    const csResult = document.getElementById('cs-modal-result');
    const csMetricsGrid = document.getElementById('cs-modal-metrics');

    window.openCaseStudyModal = (index) => {
        const project = projectsData[index] || projectsData[0];
        if (!caseStudyModal) return;

        if (csCategory) csCategory.textContent = project.category;
        if (csTitle) csTitle.textContent = project.title;
        if (csSubtitle) csSubtitle.textContent = project.subtitle;
        if (csImage) csImage.src = project.image;
        if (csDesc) csDesc.textContent = project.description;

        if (project.fullCaseStudy) {
            if (csObjective) csObjective.textContent = project.fullCaseStudy.objective;
            if (csTech) csTech.textContent = project.fullCaseStudy.technology;
            if (csWorkflow) csWorkflow.textContent = project.fullCaseStudy.workflow;
            if (csResult) csResult.textContent = project.fullCaseStudy.results;
        }

        if (csMetricsGrid) {
            csMetricsGrid.innerHTML = '';
            project.metrics.forEach(m => {
                const item = document.createElement('div');
                item.className = 'bg-black/50 border border-white/10 p-3 rounded';
                item.innerHTML = `
                    <span class="text-[9px] font-extrabold text-primary-orange block uppercase tracking-wider">${m.label}</span>
                    <span class="text-xs font-bold text-white block mt-0.5">${m.value}</span>
                `;
                csMetricsGrid.appendChild(item);
            });
        }

        caseStudyModal.classList.remove('hidden');
        caseStudyModal.classList.add('flex');
    };

    window.closeCaseStudyModal = () => {
        if (caseStudyModal) {
            caseStudyModal.classList.add('hidden');
            caseStudyModal.classList.remove('flex');
        }
    };


    // ==============================================================================
    // 3. TABBED TRAINING SELECTOR ENGINE
    // ==============================================================================
    let activeTrainingTab = 0;
    const trainingTabsContainer = document.getElementById('training-tabs-container');
    const trainingDetailCard = document.getElementById('training-detail-card');

    const renderTrainingTabs = () => {
        if (!trainingTabsContainer) return;
        trainingTabsContainer.innerHTML = '';

        trainingProgramsData.forEach((prog, idx) => {
            const tab = document.createElement('div');
            tab.className = `training-tab-item ${idx === activeTrainingTab ? 'active' : ''}`;
            tab.innerHTML = `
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="font-heading font-black text-sm text-primary-orange">${prog.id}</span>
                        <h4 class="font-bold text-white text-base">${prog.title}</h4>
                    </div>
                    <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </div>
            `;

            tab.addEventListener('click', () => {
                activeTrainingTab = idx;
                renderTrainingTabs();
                updateTrainingDetail(idx);
            });

            trainingTabsContainer.appendChild(tab);
        });
    };

    const updateTrainingDetail = (idx) => {
        if (!trainingDetailCard) return;
        const current = trainingProgramsData[idx];

        trainingDetailCard.innerHTML = `
            <div class="space-y-6">
                <div class="flex items-center justify-between pb-4 border-b border-white/10">
                    <div>
                        <span class="text-xs font-extrabold uppercase tracking-widest text-primary-orange block mb-1">${current.subtitle}</span>
                        <h3 class="text-2xl md:text-3xl font-black text-white">${current.title}</h3>
                    </div>
                    <span class="text-xs font-semibold bg-primary-orange/20 text-primary-orange border border-primary-orange/30 px-3 py-1.5 rounded-full">${current.duration}</span>
                </div>

                <p class="text-sm text-slate-300 leading-relaxed">${current.description}</p>

                <div class="p-4 rounded-lg bg-black/40 border border-white/10">
                    <span class="text-[11px] font-extrabold uppercase tracking-widest text-slate-400 block mb-1">TARGET PESERTA</span>
                    <span class="text-sm font-bold text-white">${current.target}</span>
                </div>

                <div>
                    <h4 class="text-xs font-extrabold uppercase tracking-widest text-slate-300 mb-3">MATERI KURIKULUM UNGGULAN</h4>
                    <ul class="space-y-2.5">
                        ${current.features.map(f => `
                            <li class="flex items-center gap-2.5 text-xs text-slate-300">
                                <svg class="w-4 h-4 text-primary-orange shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                <span>${f}</span>
                            </li>
                        `).join('')}
                    </ul>
                </div>

                <div class="pt-4">
                    <a href="https://wa.me/6285600604388" target="_blank" class="btn-primary w-full py-3.5 flex justify-center items-center gap-2">
                        <span>Diskusikan Modul Ini di WhatsApp</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </a>
                </div>
            </div>
        `;
    };

    renderTrainingTabs();
    updateTrainingDetail(0);


    // ==============================================================================
    // 4. STICKY NAVBAR SCROLL EFFECT
    // ==============================================================================
    const navbar = document.querySelector('.navbar-sticky');
    if (navbar) {
        window.addEventListener('scroll', () => {
            if (window.scrollY > 40) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    }

    // ==============================================================================
    // 5. MOBILE NAVIGATION DRAWER
    // ==============================================================================
    const mobileMenuBtn = document.getElementById('mobile-menu-toggle');
    const mobileDrawer = document.getElementById('mobile-drawer');
    const closeDrawerBtn = document.getElementById('close-drawer-btn');

    if (mobileMenuBtn && mobileDrawer) {
        const toggleMobileDrawer = () => {
            mobileDrawer.classList.toggle('hidden');
        };

        mobileMenuBtn.addEventListener('click', toggleMobileDrawer);
        if (closeDrawerBtn) closeDrawerBtn.addEventListener('click', toggleMobileDrawer);

        const mobileLinks = mobileDrawer.querySelectorAll('a');
        mobileLinks.forEach(link => {
            link.addEventListener('click', () => {
                mobileDrawer.classList.add('hidden');
            });
        });
    }

    // ==============================================================================
    // 6. ANIMATED STAT COUNTERS
    // ==============================================================================
    const statCounters = document.querySelectorAll('.stat-number');
    if (statCounters.length > 0) {
        const counterObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const el = entry.target;
                    const targetVal = parseInt(el.getAttribute('data-target') || '0', 10);
                    const suffix = el.getAttribute('data-suffix') || '';
                    let currentVal = 0;
                    const step = Math.max(1, Math.ceil(targetVal / 40));

                    const updateCounter = () => {
                        currentVal += step;
                        if (currentVal >= targetVal) {
                            el.textContent = targetVal + suffix;
                        } else {
                            el.textContent = currentVal + suffix;
                            requestAnimationFrame(updateCounter);
                        }
                    };

                    updateCounter();
                    observer.unobserve(el);
                }
            });
        }, { threshold: 0.5 });

        statCounters.forEach(counter => counterObserver.observe(counter));
    }

    // ==============================================================================
    // 7. FAQ ACCORDION MECHANISM
    // ==============================================================================
    const accordionItems = document.querySelectorAll('.accordion-item');
    accordionItems.forEach(item => {
        const header = item.querySelector('.accordion-header');
        const content = item.querySelector('.accordion-content');

        if (header && content) {
            header.addEventListener('click', () => {
                const isActive = item.classList.contains('active');

                accordionItems.forEach(otherItem => {
                    otherItem.classList.remove('active');
                    const otherContent = otherItem.querySelector('.accordion-content');
                    if (otherContent) otherContent.style.maxHeight = null;
                });

                if (!isActive) {
                    item.classList.add('active');
                    content.style.maxHeight = content.scrollHeight + 'px';
                }
            });
        }
    });

    // ==============================================================================
    // 8. AI CHATBOT INTEGRATION
    // ==============================================================================
    const chatToggleBtn = document.getElementById('chat-toggle-btn');
    const chatWindow = document.getElementById('chat-window-container');
    const closeChatBtn = document.getElementById('close-chat-btn');
    const chatForm = document.getElementById('chat-form-input');
    const chatInput = document.getElementById('chat-text-field');
    const chatMessagesContainer = document.getElementById('chat-messages-body');

    if (chatToggleBtn && chatWindow) {
        const toggleChat = () => {
            chatWindow.classList.toggle('chat-hidden');
            if (!chatWindow.classList.contains('chat-hidden') && chatInput) {
                chatInput.focus();
            }
        };

        chatToggleBtn.addEventListener('click', toggleChat);
        if (closeChatBtn) closeChatBtn.addEventListener('click', toggleChat);

        const appendChatMessage = (text, isUser = false) => {
            const wrapper = document.createElement('div');
            wrapper.className = `flex ${isUser ? 'justify-end' : 'justify-start'} mb-3`;

            const bubble = document.createElement('div');
            bubble.className = `max-w-[82%] p-3 text-xs leading-relaxed ${isUser ? 'chat-bubble-user' : 'chat-bubble-ai'}`;
            bubble.style.wordBreak = 'break-word';
            bubble.innerHTML = text;

            wrapper.appendChild(bubble);
            chatMessagesContainer.appendChild(wrapper);
            chatMessagesContainer.scrollTop = chatMessagesContainer.scrollHeight;
        };

        const showTypingIndicator = () => {
            const typingWrapper = document.createElement('div');
            typingWrapper.id = 'chat-typing-wrapper';
            typingWrapper.className = 'flex justify-start mb-3';
            typingWrapper.innerHTML = `
                <div class="chat-bubble-ai p-3 rounded-xl flex items-center gap-1.5">
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                </div>
            `;
            chatMessagesContainer.appendChild(typingWrapper);
            chatMessagesContainer.scrollTop = chatMessagesContainer.scrollHeight;
        };

        const removeTypingIndicator = () => {
            const typingWrapper = document.getElementById('chat-typing-wrapper');
            if (typingWrapper) typingWrapper.remove();
        };

        if (chatForm) {
            chatForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const msg = chatInput.value.trim();
                if (!msg) return;

                appendChatMessage(msg, true);
                chatInput.value = '';
                showTypingIndicator();

                try {
                    const res = await fetch('chat.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ message: msg })
                    });
                    const data = await res.json();
                    removeTypingIndicator();

                    if (data.status === 'success') {
                        appendChatMessage(data.message);
                    } else {
                        appendChatMessage("Maaf, terjadi masalah: " + data.message);
                    }
                } catch (error) {
                    removeTypingIndicator();
                    appendChatMessage("Maaf, jaringan gagal terhubung ke server chat.");
                }
            });
        }
    }
});
