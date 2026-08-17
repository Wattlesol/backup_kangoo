<?php

return [
    'terminology' => [
        'booking' => 'request',
        'bookings' => 'requests',
        'handyman' => 'employee',
        'handymen' => 'employees',
        'provider' => 'partner',
        'providers' => 'partners',
    ],

    'roles' => [
        'admin' => [
            'admin',
            'demo_admin',
        ],
        'partner' => [
            'provider',
        ],
        'employee' => [
            'handyman',
        ],
        'customer' => [
            'user',
        ],
    ],

    'request_lifecycle' => [
        'draft',
        'submitted',
        'pending_review',
        'assigned_to_partner',
        'assigned_to_employee',
        'in_progress',
        'waiting_for_documents',
        'government_processing',
        'legal_review',
        'accounting',
        'quality_review',
        'ready_for_delivery',
        'awaiting_customer_action',
        'awaiting_quality_review',
        'completed',
        'rejected',
        'cancelled',
        'escalated',
    ],

    'document_visibility' => [
        'admin',
        'provider',
        'handyman',
        'user',
        'customer',
    ],

    'ai' => [
        'enabled' => env('SANAD_AI_ENABLED', true),
        'provider' => env('SANAD_AI_PROVIDER', 'nvidia'),
        'base_url' => env('NVIDIA_AI_BASE_URL', 'https://integrate.api.nvidia.com/v1'),
        'api_key' => env('NVIDIA_API_KEY'),
        'model' => env('NVIDIA_AI_MODEL', 'nvidia/nemotron-3.5-lightning-30b-a3b'),
        'ocr_model' => env('NVIDIA_AI_OCR_MODEL', env('NVIDIA_AI_MODEL', 'nvidia/nemotron-3.5-lightning-30b-a3b')),
        'embedding_model' => env('NVIDIA_AI_EMBEDDING_MODEL', 'nvidia/nv-embedqa-e5-v5'),
        'temperature' => env('SANAD_AI_TEMPERATURE', 0.2),
        'max_tokens' => env('SANAD_AI_MAX_TOKENS', 2048),
        'requires_escalation_when_confidence_below' => env('SANAD_AI_ESCALATION_THRESHOLD', 0.65),
        'vector_store' => env('SANAD_VECTOR_STORE', 'database'),
        'chunk_size' => env('SANAD_RAG_CHUNK_SIZE', 900),
        'chunk_overlap' => env('SANAD_RAG_CHUNK_OVERLAP', 120),
        'chroma_url' => env('CHROMA_URL', 'http://127.0.0.1:8000'),
        'chroma_collection' => env('CHROMA_COLLECTION', 'sanad_knowledge_base'),
        'crawler' => [
            'base_url' => env('CRAWL4AI_BASE_URL', 'http://127.0.0.1:11235'),
            'fallback_base_url' => env('CRAWL4AI_FALLBACK_BASE_URL', 'http://sanad-crawl4ai-ase2ny:11235'),
            'api_token' => env('CRAWL4AI_API_TOKEN'),
            'timeout' => env('CRAWL4AI_TIMEOUT', 30),
            'max_pages' => env('CRAWL4AI_MAX_PAGES', 50),
            'proxy' => env('CRAWL4AI_PROXY'),
        ],
        'langsmith' => [
            'enabled' => env('LANGSMITH_TRACING', true),
            'api_key' => env('LANGSMITH_API_KEY'),
            'endpoint' => env('LANGSMITH_ENDPOINT', 'https://api.smith.langchain.com'),
            'project' => env('LANGSMITH_PROJECT', 'sanad-ai'),
            'ocr_project' => env('LANGSMITH_OCR_PROJECT', 'sanad-ocr'),
        ],
        'honcho' => [
            'enabled' => env('HONCHO_ENABLED', false),
            'api_key' => env('HONCHO_API_KEY'),
            'base_url' => env('HONCHO_BASE_URL', 'https://api.honcho.dev'),
        ],
    ],
];
