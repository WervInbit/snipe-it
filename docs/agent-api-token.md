# Agent API Token

The local Windows agent must authenticate when submitting reports such as test
results.
Generate a shared token and set it in the server's `.env` file:

```
AGENT_API_TOKEN=your-secret
```

Every request must specify a `type` indicating the report being submitted.
Workflow reports accept `test_results` and the preferred `workflow_results`
alias. Additional report types (such as wipe certificates) may be added later.

Configure the agent to include the token in the `Authorization` header when
calling `POST /api/v1/agent/reports` with `type` set to `test_results`:

```
Authorization: Bearer your-secret
```

Requests with a missing or incorrect token will receive a `401 Unauthorized`
response. You may further restrict access by whitelisting specific IPs via
`AGENT_ALLOWED_IPS=ip1,ip2` in your environment. Every submission is logged
with the asset tag and the originating IP for audit purposes.

If you want test runs to be attributed to a specific user in the UI and audit
logs, create a user for the agent and expose its ID via:

```
AGENT_USER_ID=123
```

The agent test runs will then appear under that user.

On success, the API returns a `200 OK` with `workflow_run_id` and the compatible
`test_run_id` alias. If the asset tag is unknown, a `404` is returned, and
invalid payloads receive a `400` with error details.

The agent cannot bypass workflow dependencies, resume an existing open run, or
confirm a guarded repeat. When the requested profile is blocked, the endpoint
returns `409 Conflict` with the profile state and structured dependency
blockers. A permitted human must resolve the prerequisite or use the web UI to
record an explicit override reason.

Token-only reports can execute Operator-level profiles. Senior- or
Supervisor-level profiles require `AGENT_USER_ID` to identify an enabled user
who has the corresponding `tests.execute.senior` or
`tests.execute.supervisor` ability. This restriction is checked when creating
the run; agents still cannot start a replacement run or exercise either human
override permission.

