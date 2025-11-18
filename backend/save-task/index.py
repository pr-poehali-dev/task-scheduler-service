import json
import os
import psycopg2
from typing import Dict, Any

def handler(event: Dict[str, Any], context: Any) -> Dict[str, Any]:
    '''
    Business: Save task to database and create assignments
    Args: event with httpMethod POST and task data
          context with request_id
    Returns: HTTP response with created task ID
    '''
    method: str = event.get('httpMethod', 'POST')
    
    if method == 'OPTIONS':
        return {
            'statusCode': 200,
            'headers': {
                'Access-Control-Allow-Origin': '*',
                'Access-Control-Allow-Methods': 'POST, OPTIONS',
                'Access-Control-Allow-Headers': 'Content-Type',
                'Access-Control-Max-Age': '86400'
            },
            'body': ''
        }
    
    if method != 'POST':
        return {
            'statusCode': 405,
            'headers': {
                'Content-Type': 'application/json',
                'Access-Control-Allow-Origin': '*'
            },
            'isBase64Encoded': False,
            'body': json.dumps({'error': 'Method not allowed'})
        }
    
    try:
        body_data = json.loads(event.get('body', '{}'))
        title = body_data.get('title')
        priority = body_data.get('priority', 'medium')
        urgent = body_data.get('urgent', False)
        deadline = body_data.get('deadline')
        created_by = body_data.get('createdBy')
        assigned_to = body_data.get('assignedTo', [])
        
        if not title:
            return {
                'statusCode': 400,
                'headers': {
                    'Content-Type': 'application/json',
                    'Access-Control-Allow-Origin': '*'
                },
                'isBase64Encoded': False,
                'body': json.dumps({'error': 'Title is required'})
            }
        
        conn = psycopg2.connect(os.environ['DATABASE_URL'])
        cur = conn.cursor()
        
        cur.execute(
            "INSERT INTO tasks (title, priority, urgent, deadline, created_by) "
            "VALUES (%s, %s, %s, %s, %s) RETURNING id",
            (title, priority, urgent, deadline, created_by)
        )
        task_id = cur.fetchone()[0]
        
        for user_name in assigned_to:
            cur.execute(
                "SELECT email FROM users WHERE name = %s",
                (user_name,)
            )
            result = cur.fetchone()
            if result:
                user_email = result[0]
                cur.execute(
                    "INSERT INTO task_assignments (task_id, user_email) VALUES (%s, %s)",
                    (task_id, user_email)
                )
        
        conn.commit()
        cur.close()
        conn.close()
        
        return {
            'statusCode': 200,
            'headers': {
                'Content-Type': 'application/json',
                'Access-Control-Allow-Origin': '*'
            },
            'isBase64Encoded': False,
            'body': json.dumps({
                'success': True,
                'taskId': task_id
            })
        }
        
    except Exception as e:
        return {
            'statusCode': 500,
            'headers': {
                'Content-Type': 'application/json',
                'Access-Control-Allow-Origin': '*'
            },
            'isBase64Encoded': False,
            'body': json.dumps({'error': str(e)})
        }
