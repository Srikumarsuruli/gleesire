## 1. Overview

This API allows Botamation to automatically import enquiries from various channels (WhatsApp, Instagram, Facebook, etc.) into the Gleesire Lead Management System.

**Base URL:** `https://leads.gleesire.com/api/`

---

## 2. Authentication

### API Key Authentication
- **Method:** Bearer Token
- **Header:** `Authorization: Bearer YOUR_API_KEY`
- **API Key Location:** Contact system administrator for API key

---

## 3. Endpoints

### 3.1 Create Enquiry
**Endpoint:** `POST /enquiries`

**Description:** Creates a new enquiry in the lead management system

#### Request Headers
```
Content-Type: application/json
Authorization: Bearer YOUR_API_KEY
```

#### Request Body (JSON)
```json
{
  "customer_name": "John Doe",
  "mobile_number": "+919876543210",
  "email": "john.doe@example.com",
  "source_channel": "whatsapp",
  "department_id": 1,
  "source_id": 2,
  "ad_campaign_id": 3,
  "referral_code": "REF123",
  "social_media_link": "https://wa.me/919876543210",
  "enquiry_type": "Travel Package",
  "status": "New",
  "customer_location": "Mumbai, India",
  "secondary_contact": "+919876543211",
  "destination": "Goa",
  "other_details": "Family vacation",
  "travel_month": "January",
  "travel_start_date": "2024-01-15",
  "travel_end_date": "2024-01-20",
  "night_day": "4N/5D",
  "adults_count": 2,
  "children_count": 1,
  "infants_count": 0,
  "children_age_details": "8 years",
  "customer_available_timing": "10 AM - 6 PM",
  "lead_type": "Hot",
  "conversation_data": {
    "platform": "whatsapp",
    "conversation_id": "conv_12345",
    "messages": [
      {
        "timestamp": "2024-01-10T10:30:00Z",
        "sender": "customer",
        "message": "Hi, I need a travel package for Goa"
      }
    ]
  }
}
```

#### Required Fields
- `customer_name` (string, max 255 chars)
- `mobile_number` (string, max 20 chars)
- `source_channel` (string: whatsapp, instagram, facebook, telegram, etc.)
- `department_id` (integer)
- `source_id` (integer)

#### Optional Fields
- `email` (string, valid email format)
- `ad_campaign_id` (integer)
- `referral_code` (string, max 50 chars)
- `social_media_link` (string, max 500 chars)
- `enquiry_type` (string, max 100 chars)
- `status` (string: New, In Progress, Converted, Rejected)
- `customer_location` (string, max 255 chars)
- `secondary_contact` (string, max 20 chars)
- `destination` (string, max 100 chars)
- `other_details` (text)
- `travel_month` (string: January-December or Custom)
- `travel_start_date` (date: YYYY-MM-DD)
- `travel_end_date` (date: YYYY-MM-DD)
- `night_day` (string, max 20 chars)
- `adults_count` (integer, default: 0)
- `children_count` (integer, default: 0)
- `infants_count` (integer, default: 0)
- `children_age_details` (string, max 255 chars)
- `customer_available_timing` (string, max 100 chars)
- `lead_type` (string: Hot, Warm, Cold)
- `conversation_data` (object: chat history and metadata)

#### Response - Success (201 Created)
```json
{
  "success": true,
  "message": "Enquiry created successfully",
  "data": {
    "enquiry_id": 12345,
    "lead_number": "ENQ-2024-001234",
    "enquiry_number": "GH-2024-001234",
    "status": "New",
    "created_at": "2024-01-10T10:30:00Z"
  }
}
```

#### Response - Error (400 Bad Request)
```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "customer_name": "Customer name is required",
    "mobile_number": "Invalid mobile number format"
  }
}
```

---

### 3.2 Update Enquiry
**Endpoint:** `PUT /enquiries/{enquiry_id}`

**Description:** Updates an existing enquiry

#### Request Body (JSON)
```json
{
  "status": "In Progress",
  "customer_location": "Updated location",
  "conversation_data": {
    "messages": [
      {
        "timestamp": "2024-01-10T11:00:00Z",
        "sender": "customer",
        "message": "Can you send me more details?"
      }
    ]
  }
}
```

#### Response - Success (200 OK)
```json
{
  "success": true,
  "message": "Enquiry updated successfully",
  "data": {
    "enquiry_id": 12345,
    "updated_at": "2024-01-10T11:00:00Z"
  }
}
```

---

### 3.3 Get All Chats
**Endpoint:** `GET /chats`

**Description:** Retrieves all chat conversations from multiple social media platforms via UrbanChat integration

#### Request Headers
```
Authorization: Bearer YOUR_API_KEY
```

#### Query Parameters (Optional)
- `platform` (string): Filter by platform (whatsapp, instagram, facebook, telegram)
- `date_from` (date): Start date filter (YYYY-MM-DD)
- `date_to` (date): End date filter (YYYY-MM-DD)
- `status` (string): Filter by conversation status (active, closed, pending)
- `limit` (integer): Number of conversations to return (default: 100, max: 1000)
- `offset` (integer): Pagination offset (default: 0)

#### Example Request
```
GET /chats?platform=whatsapp&date_from=2024-01-01&limit=50
```

#### Response - Success (200 OK)
```json
{
  "success": true,
  "message": "Chats retrieved successfully",
  "data": {
    "total_conversations": 150,
    "returned_count": 50,
    "conversations": [
      {
        "conversation_id": "conv_12345",
        "platform": "whatsapp",
        "customer_name": "John Doe",
        "customer_phone": "+919876543210",
        "customer_email": "john.doe@example.com",
        "status": "active",
        "created_at": "2024-01-10T10:30:00Z",
        "updated_at": "2024-01-10T15:45:00Z",
        "last_message_at": "2024-01-10T15:45:00Z",
        "message_count": 12,
        "agent_assigned": "Agent Smith",
        "tags": ["travel", "goa", "family"],
        "messages": [
          {
            "message_id": "msg_001",
            "timestamp": "2024-01-10T10:30:00Z",
            "sender": "customer",
            "sender_name": "John Doe",
            "message_type": "text",
            "content": "Hi, I need a travel package for Goa",
            "attachments": []
          },
          {
            "message_id": "msg_002",
            "timestamp": "2024-01-10T10:35:00Z",
            "sender": "agent",
            "sender_name": "Agent Smith",
            "message_type": "text",
            "content": "Hello! I'd be happy to help you with Goa packages.",
            "attachments": []
          },
          {
            "message_id": "msg_003",
            "timestamp": "2024-01-10T10:40:00Z",
            "sender": "customer",
            "sender_name": "John Doe",
            "message_type": "image",
            "content": "Here's my preferred hotel",
            "attachments": [
              {
                "type": "image",
                "url": "https://urbanchat.com/files/img_12345.jpg",
                "filename": "hotel_preference.jpg",
                "size": 245760
              }
            ]
          }
        ],
        "metadata": {
          "source_url": "https://wa.me/919876543210",
          "platform_user_id": "wa_user_12345",
          "conversation_rating": 5,
          "resolution_time": "2 hours",
          "department": "Travel Booking"
        }
      }
    ],
    "pagination": {
      "current_page": 1,
      "total_pages": 3,
      "has_next": true,
      "next_offset": 50
    }
  }
}
```

#### Response - Error (400 Bad Request)
```json
{
  "success": false,
  "message": "Invalid query parameters",
  "errors": {
    "date_from": "Invalid date format. Use YYYY-MM-DD",
    "limit": "Limit cannot exceed 1000"
  }
}
```

---

### 3.4 Get Single Chat Conversation
**Endpoint:** `GET /chats/{conversation_id}`

**Description:** Retrieves a specific chat conversation with full message history

#### Response - Success (200 OK)
```json
{
  "success": true,
  "message": "Chat conversation retrieved successfully",
  "data": {
    "conversation_id": "conv_12345",
    "platform": "whatsapp",
    "customer_name": "John Doe",
    "customer_phone": "+919876543210",
    "status": "active",
    "created_at": "2024-01-10T10:30:00Z",
    "messages": [
      {
        "message_id": "msg_001",
        "timestamp": "2024-01-10T10:30:00Z",
        "sender": "customer",
        "message_type": "text",
        "content": "Hi, I need a travel package for Goa"
      }
    ]
  }
}
```

---

### 3.5 Get Master Data
**Endpoint:** `GET /master-data`

**Description:** Retrieves all master data for dropdowns (departments, sources, campaigns, etc.)

#### Response - Success (200 OK)
```json
{
  "success": true,
  "data": {
    "departments": [
      {"id": 1, "name": "Domestic Tourism"},
      {"id": 2, "name": "International Tourism"}
    ],
    "sources": [
      {"id": 1, "name": "WhatsApp"},
      {"id": 2, "name": "Instagram"},
      {"id": 3, "name": "Facebook"}
    ],
    "ad_campaigns": [
      {"id": 1, "name": "Summer Campaign 2024"},
      {"id": 2, "name": "Goa Special"}
    ],
    "destinations": [
      {"id": 1, "name": "Goa"},
      {"id": 2, "name": "Kerala"}
    ],
    "enquiry_types": [
      {"id": 1, "name": "Travel Package"},
      {"id": 2, "name": "Hotel Booking"}
    ],
    "lead_statuses": [
      {"id": 1, "name": "New"},
      {"id": 2, "name": "In Progress"},
      {"id": 3, "name": "Converted"}
    ]
  }
}
```

---

## 4. Channel Mapping

### 4.1 Source Channel to Source ID Mapping
```json
{
  "whatsapp": 1,
  "instagram": 2,
  "facebook": 3,
  "telegram": 4,
  "website": 5,
  "phone": 6,
  "email": 7
}
```

### 4.2 Department Mapping
```json
{
  "domestic": 1,
  "international": 2,
  "medical_tourism": 3,
  "corporate": 4
}
```

---

## 5. UrbanChat Integration

### 5.1 Chat Platform Mapping
```json
{
  "whatsapp": {
    "platform_id": "wa",
    "source_id": 1,
    "webhook_url": "https://urbanchat.com/webhook/whatsapp"
  },
  "instagram": {
    "platform_id": "ig",
    "source_id": 2,
    "webhook_url": "https://urbanchat.com/webhook/instagram"
  },
  "facebook": {
    "platform_id": "fb",
    "source_id": 3,
    "webhook_url": "https://urbanchat.com/webhook/facebook"
  },
  "telegram": {
    "platform_id": "tg",
    "source_id": 4,
    "webhook_url": "https://urbanchat.com/webhook/telegram"
  }
}
```

### 5.2 UrbanChat API Configuration
- **UrbanChat Base URL:** `https://api.urbanchat.com/v1/`
- **Authentication:** API Key in header
- **Rate Limit:** 1000 requests per hour
- **Sync Frequency:** Real-time via webhooks + hourly batch sync

### 5.3 Message Types Supported
```json
{
  "text": "Plain text messages",
  "image": "Image files (jpg, png, gif)",
  "video": "Video files (mp4, avi)",
  "audio": "Audio files (mp3, wav)",
  "document": "Documents (pdf, doc, xlsx)",
  "location": "GPS coordinates",
  "contact": "Contact information",
  "sticker": "Stickers and emojis"
}
```

---

## 6. Webhook Configuration

### 5.1 Webhook URL
**URL:** `https://leads.gleesire.com/api/webhook/botamation`

**Method:** `POST`

**Description:** Botamation can send real-time enquiry data to this webhook

#### Webhook Payload
```json
{
  "event": "new_enquiry",
  "timestamp": "2024-01-10T10:30:00Z",
  "platform": "whatsapp",
  "conversation_id": "conv_12345",
  "customer": {
    "name": "John Doe",
    "phone": "+919876543210",
    "email": "john.doe@example.com"
  },
  "enquiry_data": {
    "destination": "Goa",
    "travel_dates": "15-20 Jan 2024",
    "passengers": "2 Adults, 1 Child"
  },
  "conversation": [
    {
      "timestamp": "2024-01-10T10:30:00Z",
      "sender": "customer",
      "message": "Hi, I need a travel package for Goa"
    }
  ]
}
```

---

## 7. Error Codes

| Code | Message | Description |
|------|---------|-------------|
| 200 | Success | Request successful |
| 201 | Created | Resource created successfully |
| 400 | Bad Request | Invalid request data |
| 401 | Unauthorized | Invalid or missing API key |
| 404 | Not Found | Resource not found |
| 422 | Validation Error | Data validation failed |
| 500 | Internal Server Error | Server error |

---

## 8. Testing

### 8.1 Test Environment
- **Base URL:** `https://leads.gleesire.com/api/test/`
- **Test API Key:** Contact administrator

### 8.2 Sample cURL Requests

#### Create Enquiry
```bash
curl -X POST https://leads.gleesire.com/api/enquiries \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_API_KEY" \
  -d '{
    "customer_name": "Test Customer",
    "mobile_number": "9876543210",
    "source_channel": "whatsapp",
    "department_id": 1,
    "source_id": 1,
    "enquiry_type": "Travel Package"
  }'
```

#### Get All Chats
```bash
curl -X GET "https://leads.gleesire.com/api/chats?platform=whatsapp&limit=50" \
  -H "Authorization: Bearer YOUR_API_KEY"
```

#### Get Specific Chat
```bash
curl -X GET https://leads.gleesire.com/api/chats/conv_12345 \
  -H "Authorization: Bearer YOUR_API_KEY"
```

---

