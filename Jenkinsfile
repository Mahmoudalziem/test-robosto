pipeline {
    agent any
    stages {
        stage('develop') {
            when {
                branch 'develop'
            }
            steps {
                sshagent (['ee611816-6d1a-4888-80aa-8df0d1733b39']) {
                    sh "ssh -o StrictHostKeyChecking=no -l forge backenddev.robostodelivery.com -p15978 'cd /home/forge/dev.backend.robostodelivery.com && git stash && git pull ; docker build -t glocom/robosto-backend:v1 . && docker push glocom/robosto-backend:dev'" 
                }
            }
        }
        stage('staging') {
            when {
                branch 'staging'
            }
            steps {
                sshagent (['ee611816-6d1a-4888-80aa-8df0d1733b39']) {
                    sh "ssh -o StrictHostKeyChecking=no -l forge backenddev.robostodelivery.com -p15978 'cd /home/forge/staging-backend.robostodelivery.com && bash -x build.sh'" 
                }
            }
        }
    }
}
