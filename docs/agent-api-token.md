# Agent API Token

The local Windows agent must authenticate when submitting test results.
Generate a shared token and set it in the server's `.env` file:

```
AGENT_API_TOKEN=your-secret
```

Configure the agent to include this token in the `Authorization` header when
calling `POST /api/v1/agent/test-results`:

```
Authorization: Bearer your-secret
```

Requests with a missing or incorrect token will receive a `401 Unauthorized`
response. On success, the API returns a `200 OK` with the new `test_run_id`.
If the asset tag is unknown, a `404` is returned, and invalid payloads
receive a `400` with error details.

